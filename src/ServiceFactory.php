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
        $this->api = 'https://us.api.blizzard.com';
        $this->oauth = 'https://us.battle.net/';
    }

    /**
     * @param string $clientId : client id from blizzard
     * @param string $clientSecret : secret id from blizzard
     * @param string $accessToken : access token from oauth
     * @return WorldOfWarcraft
     */
    public function createAccountService($clientId, $clientSecret, $accessToken)
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Account(
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
     * @return WorldOfWarcraft\CharacterMedia
     */
    public function createCharacterMediaService($clientId, $clientSecret, $accessToken) : WorldOfWarcraft\CharacterMedia
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\CharacterMedia(
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
     * @return WorldOfWarcraft\Item
     */
    public function createItemService($clientId, $clientSecret, $accessToken) : WorldOfWarcraft\Item
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Item(
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
     * @return WorldOfWarcraft\Mount
     */
    public function createMountService(string $clientId, string $clientSecret, string $accessToken) : WorldOfWarcraft\Mount
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Mount(
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
     * @return WorldOfWarcraft\MythicPlus
     */
    public function createMythicPlusService(string $clientId, string $clientSecret, string $accessToken) : WorldOfWarcraft\MythicPlus
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\MythicPlus(
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
     * @return WorldOfWarcraft\Pet
     */
    public function createPetService($clientId, $clientSecret, $accessToken) : WorldOfWarcraft\Pet
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Pet(
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
     * @return WorldOfWarcraft\Realm
     */
    public function createRealmService($clientId, $clientSecret, $accessToken) : WorldOfWarcraft\Realm
    {
        $apiAdapter = new Adapter\GuzzleAdapter($this->api);
        $oauthAdapter = new Adapter\GuzzleAdapter($this->oauth);

        $service = new WorldOfWarcraft\Realm(
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
