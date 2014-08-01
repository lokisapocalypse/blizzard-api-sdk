<?php
namespace Fusani\Auth;

/**
 * This class interacts with the fusani auth api.
 */
class Client
{
    // a way to connect to the api
    protected $adapter;

    /**
     * This function initializes the client by setting the adapter.
     *
     * @param \Fusani\Auth\Adapter\Adapter $adapter : an adapter for interfacing with the api
     * @return void
     */
    public function __construct(\Fusani\Auth\Adapter\Adapter $adapter)
    {
        $this->adapter = $adapter;
    }
}
