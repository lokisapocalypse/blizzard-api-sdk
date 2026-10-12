<?php

declare(strict_types=1);

namespace Fusani\Blizzard\OAuth;

use Closure;
use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\Adapter\GuzzleAdapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheException;

/**
 * Gets OAuth tokens from each region's OAuth server, and reuses client-credentials tokens until they expire.
 *
 * Tokens are kept in memory, and in the PSR-16 cache if one is given so they can be shared between requests.
 * The US, EU, KR and TW regions share one OAuth server and so one token; China has its own.
 */
class TokenProvider
{
    /** @var string client id from blizzard */
    protected string $clientId;

    /** @var string secret id from blizzard */
    protected string $clientSecret;

    /** @var CacheInterface|null shared token store, if any */
    protected ?CacheInterface $cache;

    /** @var Closure(string): Adapter builds an adapter for an OAuth server's url */
    protected Closure $adapterFactory;

    /** @var array<string, Adapter> adapters already built, by OAuth url */
    protected array $adapters;

    /** @var array<string, AccessToken> client-credentials tokens, by OAuth url */
    protected array $tokens;

    /**
     * Build the provider from the app's credentials.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param CacheInterface|null $cache somewhere to share tokens between requests, or null to keep them in memory only
     * @param (Closure(string): Adapter)|null $adapterFactory builds an adapter for an OAuth url, or null for Guzzle
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        ?CacheInterface $cache = null,
        ?Closure $adapterFactory = null
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cache = $cache;
        $this->adapterFactory = $adapterFactory ?? static fn (string $url): Adapter => new GuzzleAdapter($url);
        $this->adapters = [];
        $this->tokens = [];
    }

    /**
     * Get a client-credentials token for a region, reusing the current one until it expires.
     *
     * @param Region $region the region the token is for
     * @return string the bearer token
     * @throws AuthenticationException if the OAuth server rejects the credentials
     * @throws BlizzardException if the request fails for any other reason
     */
    public function token(Region $region): string
    {
        $now = time();
        $host = $region->oauthHost();
        $token = $this->tokens[$host] ?? $this->cached($host);

        if ($token === null || $token->isExpired($now)) {
            return $this->refresh($region);
        }

        $this->tokens[$host] = $token;

        return $token->value();
    }

    /**
     * Get a new client-credentials token for a region, e.g. after the api rejected the current one.
     *
     * @param Region $region the region the token is for
     * @return string the bearer token
     * @throws AuthenticationException if the OAuth server rejects the credentials
     * @throws BlizzardException if the request fails for any other reason
     */
    public function refresh(Region $region): string
    {
        $now = time();
        $host = $region->oauthHost();
        $token = AccessToken::fromResponse($this->request($region, ['grant_type' => 'client_credentials']), $now);

        $this->tokens[$host] = $token;
        $this->store($host, $token, $now);

        return $token->value();
    }

    /**
     * Exchange a user's authorization code for a user token, which is used once and not stored.
     *
     * @param Region $region the region the user signed in to
     * @param string $code the authorization code from the OAuth redirect
     * @param string $redirectUri the redirect uri used to get the authorization code
     * @param string $scope the scope that was requested, e.g. `wow.profile`
     * @return string the bearer token
     * @throws AuthenticationException if the OAuth server rejects the code or credentials
     * @throws BlizzardException if the request fails for any other reason
     */
    public function exchangeAuthorizationCode(Region $region, string $code, string $redirectUri, string $scope): string
    {
        $response = $this->request($region, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
        ]);

        return AccessToken::fromResponse($response, time())->value();
    }

    /**
     * Get the adapter for an OAuth server, building it the first time.
     *
     * @param string $host the OAuth server's url
     * @return Adapter
     */
    protected function adapterFor(string $host): Adapter
    {
        return $this->adapters[$host] ??= ($this->adapterFactory)($host);
    }

    /**
     * Get the cache key for an OAuth server's token, made of characters every PSR-16 cache accepts.
     *
     * @param string $host the OAuth server's url
     * @return string
     */
    protected function cacheKey(string $host): string
    {
        return 'fusani_blizzard.token.' . hash('sha256', $host . "\n" . $this->clientId);
    }

    /**
     * Get the token for an OAuth server from the cache.
     *
     * @param string $host the OAuth server's url
     * @return AccessToken|null the token, or null if there's no cache or no token in it
     */
    protected function cached(string $host): ?AccessToken
    {
        if ($this->cache === null) {
            return null;
        }

        try {
            return AccessToken::fromArray($this->cache->get($this->cacheKey($host)));
        } catch (CacheException) {
            return null;
        }
    }

    /**
     * Post a token request to a region's OAuth server.
     *
     * @param Region $region the region whose OAuth server to use
     * @param array<string, string> $params the grant parameters
     * @return array<mixed> the OAuth server's response
     * @throws AuthenticationException if the OAuth server rejects the request
     * @throws NotFoundException if the OAuth server responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    protected function request(Region $region, array $params): array
    {
        $params += [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        return $this->adapterFor($region->oauthHost())->post('/token', $params, null);
    }

    /**
     * Save an OAuth server's token in the cache until it expires.
     *
     * @param string $host the OAuth server's url
     * @param AccessToken $token the token to save
     * @param int $now the current unix timestamp
     * @return void
     */
    protected function store(string $host, AccessToken $token, int $now): void
    {
        $ttl = $token->secondsLeft($now);

        if ($this->cache === null || $ttl === 0) {
            return;
        }

        try {
            $this->cache->set($this->cacheKey($host), $token->toArray(), $ttl);
        } catch (CacheException) {
            // A cache that can't store the token only costs a token request on the next page load.
        }
    }
}
