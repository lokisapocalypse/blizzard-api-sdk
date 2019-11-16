<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\Adapter;

class ServiceFactory
{
    /** @param api */
    protected $api;

    /** @param string */
    protected $oauth;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->api = 'https://us.api.blizzard.com/';
        $this->oauth = 'https://us.battle.net/';
    }

    /**
     * @param string $clientId : client id from blizzard
     * @param string $clientSecret : secret id from blizzard
     * @param string $accessToken : access token from oauth
     * @return WorldOfWarcraft
     */
    public function createWorldOfWarcraftService($clientId, $clientSecret, $accessToken)
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft(
            $apiAdapter,
            $oauthAdapter,
            $clientId,
            $clientSecret,
            $accessToken
        );

        return $service;
    }

    /**
     * @param string $clientId : client id from blizzard
     * @param string $clientSecret : secret id from blizzard
     * @param string $accessToken : access token from oauth
     * @return WorldOfWarcraft\Faction
     */
    public function createFactionService($clientId, $clientSecret, $accessToken) : WorldOfWarcraft\Faction
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Faction(
            $apiAdapter,
            $oauthAdapter,
            $clientId,
            $clientSecret,
            $accessToken
        );

        return $service;
    }

    /**
     * @param string $clientId : client id from blizzard
     * @param string $clientSecret : secret id from blizzard
     * @param string $accessToken : access token from oauth
     * @return WorldOfWarcraft\Reputation
     */
    public function createReputationService($clientId, $clientSecret, $accessToken) : WorldOfWarcraft\Reputation
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Reputation(
            $apiAdapter,
            $oauthAdapter,
            $clientId,
            $clientSecret,
            $accessToken
        );

        return $service;
    }
}
