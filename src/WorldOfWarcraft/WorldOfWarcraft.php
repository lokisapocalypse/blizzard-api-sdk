<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

abstract class WorldOfWarcraft
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
     * @param string $grantType : what the token has access to
     * @param array $additionalParams : any additional parameters needed to request access token
     * @return array
     */
    public function getAccessToken($grantType = 'client_credentials', $additionalParams = [])
    {
        $params = [
            'grant_type' => $grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $params = array_merge($params, $additionalParams);

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
