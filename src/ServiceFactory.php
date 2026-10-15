<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Builds the World of Warcraft api services, which share one api adapter and one token provider.
 */
class ServiceFactory
{
    /** @var Adapter\Adapter connection to the api, sent to each request's region */
    protected Adapter\Adapter $apiAdapter;

    /** @var OAuth\TokenProvider gets and reuses access tokens */
    protected OAuth\TokenProvider $tokens;

    /** @var string|null locale names come back in, or null for every locale */
    protected ?string $locale;

    /**
     * Set up the shared adapter and token provider.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param CacheInterface|null $cache somewhere to share tokens between requests, or null to keep them in memory only
     * @param string|null $locale locale names come back in, e.g. `en_US`, or null for an object of every locale
     * @param float $timeout seconds an api or OAuth request may take in total before it fails, ignored with $httpClient
     * @param float $connectTimeout seconds to wait for a connection to the api or OAuth server, ignored with $httpClient
     * @param ClientInterface|null $httpClient Guzzle client to send api and OAuth requests with, e.g. one with logging
     *     middleware or a proxy, or null for one with the given timeouts
     * @param int $maxRetries how many times to send an api or OAuth request again after a 429 or a temporary server
     *     error, waiting longer each time, or 0 to never retry
     * @throws InvalidArgumentException if no client is given and either timeout isn't more than 0, or the number of
     *     retries is negative
     */
    public function __construct(
        string $clientId,
        string $clientSecret,
        ?CacheInterface $cache = null,
        ?string $locale = null,
        float $timeout = Adapter\GuzzleAdapter::DEFAULT_TIMEOUT,
        float $connectTimeout = Adapter\GuzzleAdapter::DEFAULT_CONNECT_TIMEOUT,
        ?ClientInterface $httpClient = null,
        int $maxRetries = 0
    ) {
        $client = $httpClient ?? Adapter\GuzzleAdapter::createClient($timeout, $connectTimeout);
        $retryPolicy = new Adapter\RetryPolicy($maxRetries);

        $this->apiAdapter = new Adapter\GuzzleAdapter(Region::Us->apiHost(), $client, $retryPolicy);
        $this->tokens = new OAuth\TokenProvider(
            $clientId,
            $clientSecret,
            $cache,
            static fn (string $url): Adapter\Adapter => new Adapter\GuzzleAdapter($url, $client, $retryPolicy)
        );
        $this->locale = $locale;
    }

    /**
     * Build the account (character list) service.
     *
     * @return WorldOfWarcraft\Account
     */
    public function createAccountService(): WorldOfWarcraft\Account
    {
        return new WorldOfWarcraft\Account($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the character media service.
     *
     * @return WorldOfWarcraft\CharacterMedia
     */
    public function createCharacterMediaService(): WorldOfWarcraft\CharacterMedia
    {
        return new WorldOfWarcraft\CharacterMedia($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the faction service.
     *
     * @return WorldOfWarcraft\Faction
     */
    public function createFactionService(): WorldOfWarcraft\Faction
    {
        return new WorldOfWarcraft\Faction($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the item service.
     *
     * @return WorldOfWarcraft\Item
     */
    public function createItemService(): WorldOfWarcraft\Item
    {
        return new WorldOfWarcraft\Item($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the mount service.
     *
     * @return WorldOfWarcraft\Mount
     */
    public function createMountService(): WorldOfWarcraft\Mount
    {
        return new WorldOfWarcraft\Mount($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the Mythic+ service.
     *
     * @return WorldOfWarcraft\MythicPlus
     */
    public function createMythicPlusService(): WorldOfWarcraft\MythicPlus
    {
        return new WorldOfWarcraft\MythicPlus($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the battle pet service.
     *
     * @return WorldOfWarcraft\Pet
     */
    public function createPetService(): WorldOfWarcraft\Pet
    {
        return new WorldOfWarcraft\Pet($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the realm service.
     *
     * @return WorldOfWarcraft\Realm
     */
    public function createRealmService(): WorldOfWarcraft\Realm
    {
        return new WorldOfWarcraft\Realm($this->apiAdapter, $this->tokens, $this->locale);
    }

    /**
     * Build the reputation service.
     *
     * @return WorldOfWarcraft\Reputation
     */
    public function createReputationService(): WorldOfWarcraft\Reputation
    {
        return new WorldOfWarcraft\Reputation($this->apiAdapter, $this->tokens, $this->locale);
    }
}
