<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\Adapter;

class WorldOfWarcraft
{
    /** @var Adapter\Adapter */
    protected $adapter;

    /** @var string */
    protected $key;

    /** @var array */
    protected $params;

    /**
     * @param Adapter\Adapter $adapter : connection to the api service
     * @param string $key : api key
     */
    public function __construct(Adapter\Adapter $adapter, $key)
    {
        $this->adapter = $adapter;
        $this->key = $key;
        $this->params = ['apikey' => $key];
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
        return $this->adapter->get("/wow/character/$realm/$character", $this->params);
    }
}
