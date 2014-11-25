<?php

namespace Fusani\Fusani\Adapter;

/**
 * Testing adapter
 */
class AdapterStub implements Adapter
{
    protected $requests;
    protected $responses;

    public function __construct($url)
    {
        $this->responses = [];
    }

    public function addResponse($response)
    {
        $this->responses[] = $response;
    }

    public function call($service, $method, $params)
    {
        return $this->getNextResponse();
    }

    public function get($path, $params)
    {
        throw new \Exception('Not yet implemented');
    }

    protected function getNextResponse()
    {
        return array_shift($this->responses);
    }

    public function post($path, $params)
    {
        throw new \Exception('Not yet implemented');
    }

    public function put($path, $params)
    {
        throw new \Exception('Not yet implemented');
    }
}
