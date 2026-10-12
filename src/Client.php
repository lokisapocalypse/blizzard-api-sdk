<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

/**
 * Generic api client built on an adapter.
 */
class Client
{
    /** @var Adapter\Adapter a way to connect to the api */
    protected Adapter\Adapter $adapter;

    /**
     * Initialize the client by setting the adapter.
     *
     * @param Adapter\Adapter $adapter an adapter for interfacing with the api
     */
    public function __construct(Adapter\Adapter $adapter)
    {
        $this->adapter = $adapter;
    }
}
