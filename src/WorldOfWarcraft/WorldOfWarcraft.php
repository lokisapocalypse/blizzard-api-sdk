<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

/**
 * Base for the World of Warcraft api services: holds the adapters and credentials, and gets access tokens.
 */
abstract class WorldOfWarcraft
{
    /** @var string|null */
    protected ?string $accessToken;

    /** @var Adapter\Adapter */
    protected Adapter\Adapter $apiAdapter;

    /** @var string */
    protected string $clientId;

    /** @var string */
    protected string $clientSecret;

    /** @var Adapter\Adapter */
    protected Adapter\Adapter $oauthAdapter;

    /**
     * Build the service from its adapters and credentials.
     *
     * @param Adapter\Adapter $apiAdapter connection to the api service
     * @param Adapter\Adapter $oauthAdapter connection to the OAuth server
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     */
    public function __construct(
        Adapter\Adapter $apiAdapter,
        Adapter\Adapter $oauthAdapter,
        string $clientId,
        string $clientSecret,
        ?string $accessToken = null
    ) {
        $this->accessToken = $accessToken;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $this->apiAdapter = $apiAdapter;
        $this->oauthAdapter = $oauthAdapter;
    }

    /**
     * Get a new access token for making api requests.
     *
     * @param string $grantType what the token has access to
     * @param array<string, string> $additionalParams any additional parameters needed to request access token
     * @return array<mixed> the OAuth server's response
     * @throws AuthenticationException if the OAuth server rejects the credentials
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getAccessToken(string $grantType = 'client_credentials', array $additionalParams = []): array
    {
        $params = [
            'grant_type' => $grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $params = array_merge($params, $additionalParams);

        return $this->oauthAdapter->post('/oauth/token', $params, null);
    }

    /**
     * Update the access token for api requests.
     *
     * @return void
     * @throws AuthenticationException if the OAuth server rejects the credentials
     * @throws BlizzardException if the request fails for any other reason
     */
    public function refreshToken(): void
    {
        $this->accessToken = $this->tokenFrom($this->getAccessToken());
    }

    /**
     * Make a get request to the api, refreshing the token and retrying once if it has expired.
     *
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the query parameters that will be sent
     * @param string|null $region the region whose host the request goes to, or null for the default (US) host
     * @return array<mixed> the decoded response, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    protected function fetch(string $path, array $params, ?string $region = null): array
    {
        try {
            return $this->apiAdapter->get($path, $params, $this->accessToken, $region);
        } catch (AuthenticationException) {
            $this->refreshToken();

            $response = $this->apiAdapter->get($path, $params, $this->accessToken, $region);
            $response['access_token'] = $this->accessToken;

            return $response;
        }
    }

    /**
     * Pull the access token out of an OAuth response.
     *
     * @param array<mixed> $response the response from getAccessToken()
     * @return string|null the access token, or null if the response doesn't have one
     */
    protected function tokenFrom(array $response): ?string
    {
        if (!isset($response['access_token']) || !is_string($response['access_token'])) {
            return null;
        }

        return $response['access_token'];
    }
}
