<?php
namespace Fusani\Fusani\Adapter\Rpc;

use JsonRpc;
use React;

class JsonRpcAdapter implements Adapter
{
    protected $client;
    protected $transport;

    public function __construct($url)
    {
        $transport = new JsonRpcAdapterTransport();

        $this->client = new JsonRpc\Client($url, $transport);
        $this->transport = $transport;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed : the result of the RPC call,
     *                 false on failure
     */
    public function call($service, $method, $params)
    {
        $this->transport->setService($service);
        $success = $this->client->call($method, $params);

        if ($success) {
            return $this->client->result;
        }

        return false;
    }

    public function get($path, $params)
    {
        throw new \Exception('Not yet implemented');
    }

    public function post($path, $params)
    {
        throw new \Exception('Not yet implemented');
    }

    public function put($path, $params)
    {
        throw new \Exception('Not yet implemented');
    }

    public function setTransport($transport)
    {
        $this->transport = $transport;
        $this->client->setTransport($transport);
    }
}
