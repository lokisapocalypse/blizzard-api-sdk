<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

/**
 * Older World of Warcraft client, replaced by the services in the WorldOfWarcraft namespace.
 */
class WorldOfWarcraft
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
     * Build the client from its adapters and credentials.
     *
     * @param Adapter\Adapter $apiAdapter connection to the api service
     * @param Adapter\Adapter $oauthAdapter connection to the OAuth server
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, if there is one
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
     * @return array<mixed>|false the OAuth server's response, or false if the request failed
     */
    public function getAccessToken(): array|false
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        return $this->oauthAdapter->post('/oauth/token', $params, null);
    }

    /**
     * Update the access token for api requests.
     *
     * @return void
     */
    public function refreshToken(): void
    {
        $response = $this->getAccessToken();

        $this->accessToken = is_array($response) && is_string($response['access_token'] ?? null)
            ? $response['access_token']
            : null;
    }
}
