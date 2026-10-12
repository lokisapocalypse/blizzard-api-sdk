<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;

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
     * @return array<mixed>|false the OAuth server's response, or false if the request failed
     */
    public function getAccessToken(string $grantType = 'client_credentials', array $additionalParams = []): array|false
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
     */
    public function refreshToken(): void
    {
        $this->accessToken = $this->tokenFrom($this->getAccessToken());
    }

    /**
     * Pull the access token out of an OAuth response.
     *
     * @param array<mixed>|false $response the response from getAccessToken()
     * @return string|null the access token, or null if the response doesn't have one
     */
    protected function tokenFrom(array|false $response): ?string
    {
        if ($response === false || !isset($response['access_token']) || !is_string($response['access_token'])) {
            return null;
        }

        return $response['access_token'];
    }
}
