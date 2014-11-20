<?php

namespace Fusani\Fusani\Service;

use Fusani\Fusani\Adapter;

class SampleService
{
    protected $adapter;

    public function __construct(Adapter\Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     */
    public function sample()
    {
        // send the request to the api
        return $this->adapter->call(
            'SampleService',
            'functionName',
            []
        );
    }
}
