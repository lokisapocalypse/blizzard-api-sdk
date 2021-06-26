<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class WorldOfWarcraft
{
    /** @var string */
    protected $accessToken;

    /** @var Adapter\Adapter */
    protected $apiAdapter;

    /** @var string */
    protected $clientId;

    /** @var string */
    protected $clientSecret;

    /** @var Adapter\Adapter */
    protected $oauthAdapter;

    /**
     * @param Adapter\Adapter $adapter : connection to the api service
     * @param string $key : api key
     */
    public function __construct(
        Adapter\Adapter $apiAdapter,
        Adapter\Adapter $oauthAdapter,
        string $clientId,
        string $clientSecret,
        string $accessToken = null
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
     * @return string
     */
    public function getAccessToken()
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        return $this->oauthAdapter->post("/oauth/token", $params);
    }

    /**
     * Update the access token for api requests.
     *
     * @return void
     */
    public function refreshToken()
    {
        $response = $this->getAccessToken();

        $this->accessToken = $response['access_token'];
    }
}