<?php

namespace Fusani\Fusani\Adapter;

use Fusani\Fusani\SimpleTestCase;

/**
 * @covers Fusani\Fusani\Adapter\JsonRpcAdapterTransport
 */
class JsonRpcAdapterTransportTest extends SimpleTestCase
{
    public function testSend()
    {
        $transport = new JsonRpcAdapterTransport();
        $transport->setService('testService');

        $response = $transport->send('method', '', array());

        $this->assertNull($response);
    }
}
