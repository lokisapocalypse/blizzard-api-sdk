<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

/**
 * Builds the World of Warcraft api services, each with its own api and OAuth adapters.
 */
class ServiceFactory
{
    /** @var string base url of the game data and profile api */
    protected string $api;

    /** @var string base url of the OAuth server */
    protected string $oauth;

    /**
     * Set the default (US) api and OAuth urls.
     */
    public function __construct()
    {
        $this->api = 'https://us.api.blizzard.com';
        $this->oauth = 'https://us.battle.net/';
    }

    /**
     * Build the account (character list) service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Account
     */
    public function createAccountService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Account {
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
     * Build the character media service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\CharacterMedia
     */
    public function createCharacterMediaService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\CharacterMedia {
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
     * Build the faction service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Faction
     */
    public function createFactionService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Faction {
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
     * Build the item service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Item
     */
    public function createItemService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Item {
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
     * Build the mount service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Mount
     */
    public function createMountService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Mount {
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
     * Build the Mythic+ service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\MythicPlus
     */
    public function createMythicPlusService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\MythicPlus {
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
     * Build the battle pet service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Pet
     */
    public function createPetService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Pet {
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
     * Build the realm service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Realm
     */
    public function createRealmService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Realm {
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
     * Build the reputation service.
     *
     * @param string $clientId client id from blizzard
     * @param string $clientSecret secret id from blizzard
     * @param string|null $accessToken access token from oauth, or null to get one on the first expired request
     * @return WorldOfWarcraft\Reputation
     */
    public function createReputationService(
        string $clientId,
        string $clientSecret,
        ?string $accessToken
    ): WorldOfWarcraft\Reputation {
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
