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

        return $this->oauthAdapter->get("/oauth/token", $params);
    }

    /**
     * Get the item details based on the item id
     *
     * @param int $id : the wow api item id
     * @return array
     */
    public function item(int $id)
    {
        $params = [
            'access_token' => $this->accessToken,
        ];

        $response = $this->apiAdapter->get("/wow/item/$id", $params);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params = [
                'access_token' => $this->accessToken,
            ];

            $response = $this->apiAdapter->get("/wow/item/$id", $params);
            $response['access_token'] = $this->params['access_token'];
        }

        return $response;
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

    /**
     * Retrieve reputation data for a character
     *
     * @param string $character : name of character to fetch reputation data
     * @param string $realm : the realm the character resides on
     * @return array : raw api data
     */
    public function reputation($character, $realm)
    {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'reputation',
        ];

        $response = $this->apiAdapter->get("/wow/character/$realm/$character", $params);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params = [
                'access_token' => $this->accessToken,
                'fields' => 'reputation',
            ];

            $response = $this->apiAdapter->get("/wow/character/$realm/$character", $params);
            $response['access_token'] = $this->params['access_token'];
        }

        return $response;
    }
}
