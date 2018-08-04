<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\Adapter;

class ServiceFactory
{
    /** @param string */
    protected $url;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->url = 'https://us.api.battle.net/';
    }

    /**
     * @param string $key : api key from blizzard
     * @return WorldOfWarcraft
     */
    public function createWorldOfWarcraftService($key)
    {
        $adapter = new Adapter\GuzzleAdapter($this->url);
        $service = new WorldOfWarcraft($adapter, $key);
        return $service;
    }
}
