<?php

namespace Blizzard\WorldOfWarcraft\Adapter;

use Blizzard\WorldOfWarcraft\SimpleTestCase;

/**
 * @covers Blizzard\WorldOfWarcraft\Adapter\JsonRpcAdapterTransport
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
