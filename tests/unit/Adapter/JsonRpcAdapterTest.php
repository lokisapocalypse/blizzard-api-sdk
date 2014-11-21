<?php

namespace Fusani\Fusani\Adapter;

use Fusani\Fusani\SimpleTestCase;

/**
 * @covers Fusani\Fusani\Adapter\JsonRpcAdapter
 */
class JsonRpcAdapterTest extends SimpleTestCase
{
    public function testConstruct()
    {
        $adapter = new JsonRpcAdapter('http://www.google.com');

        $this->assertAttributeInstanceOf('JsonRpc\Client', 'client', $adapter);
    }

    public function testCall()
    {
        $adapter = new JsonRpcAdapter('http://www.google.com');

        $transport = $this->getMock('Fusani\Fusani\Adapter\JsonRpcAdapterTransport');
        $adapter->setTransport($transport);

        $expected = ['test response'];

        // Test a successful response
        $transport->expects($this->any())
            ->method('send')
            ->will($this->returnValue(true));
        $transport->output = json_encode([
            'jsonrpc' => '2.0',
            'result'  => $expected,
            'id'      => 1,
        ]);

        $result = $adapter->call('test', 'test', []);
        $this->assertEquals($expected, $result);

        // Test a failed response
        $transport->output = json_encode([
            'id'      => 1,
            'jsonrpc' => '2.0',
            'error'   => array(
                'code'    => -32000,
                'message' => 'womp womp',
            ),
        ]);

        $result = $adapter->call('test', 'test', []);
        $this->assertFalse($result);
    }

    public function testGet()
    {
        $adapter = new JsonRpcAdapter('http://www.google.com');

        try {
            $adapter->get('your', 'mom');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals('Not yet implemented', $e->getMessage());
        }
    }

    public function testPost()
    {
        $adapter = new JsonRpcAdapter('http://www.google.com');

        try {
            $adapter->post('your', 'mom');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals('Not yet implemented', $e->getMessage());
        }
    }

    public function testPut()
    {
        $adapter = new JsonRpcAdapter('http://www.google.com');

        try {
            $adapter->put('your', 'mom');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertEquals('Not yet implemented', $e->getMessage());
        }
    }
}
