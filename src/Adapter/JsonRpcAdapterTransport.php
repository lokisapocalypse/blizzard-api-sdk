<?php

namespace Fusani\Fusani\Adapter\Rpc;

use JsonRpc;

class JsonRpcAdapterTransport extends JsonRpc\Transport\BasicClient implements Adapter
{
    protected $service;

    public function setService($service)
    {
        $this->service = $service;
    }

    public function send($method, $url, $json, $headers = array())
    {
        $url = "$url/{$this->service}";

        return parent::send($method, $url, $json, $headers);
    }
}
