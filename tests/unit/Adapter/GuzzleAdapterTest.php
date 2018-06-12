<?php

namespace Blizzard\WorldOfWarcraft\Adapter;

use GuzzleHttp\Exception;
use Blizzard\WorldOfWarcraft\SimpleTestCase;

/**
 * @covers Blizzard\WorldOfWarcraft\Adapter\GuzzleAdapter
 */
class GuzzleAdapterTest extends SimpleTestCase
{
    public function mockClient($success, $method)
    {
        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->getMock();

        if (!$success) {
            $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
                ->disableOriginalConstructor()
                ->getMock();

            $exception = new Exception\BadResponseException('', $request);

            $response->expects($this->once())
                ->method('json')
                ->will($this->throwException($exception));
        }
        else {
            $response->expects($this->once())
                ->method('json')
                ->will($this->returnValue(true));
        }

        $client = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $client->expects($this->once())
            ->method($method)
            ->will($this->returnValue($response));

        return $client;
    }

    public function testConstruct()
    {
        $adapter = new GuzzleAdapter('http://www.google.com');

        $this->assertAttributeInstanceOf('GuzzleHttp\Client', 'client', $adapter);
    }

    public function testGetFailure()
    {
        $adapter = new MockGuzzleAdapter('https://www.example.com');
        $adapter->setClient($this->mockClient(false, 'get'));

        $this->assertFalse($adapter->get('/test', []));
    }

    public function testGetSuccess()
    {
        $adapter = new MockGuzzleAdapter('http://www.google.com');
        $adapter->setClient($this->mockClient(true, 'get'));

        $this->assertTrue($adapter->get('/test', []));
    }

    public function testPostFailure()
    {
        $adapter = new MockGuzzleAdapter('https://www.example.com');
        $adapter->setClient($this->mockClient(false, 'post'));

        $this->assertFalse($adapter->post('/test', []));
    }

    public function testPostSuccess()
    {
        $adapter = new MockGuzzleAdapter('http://www.google.com');
        $adapter->setClient($this->mockClient(true, 'post'));

        $this->assertTrue($adapter->post('/test', []));
    }

    public function testPutFailure()
    {
        $adapter = new MockGuzzleAdapter('https://www.example.com');
        $adapter->setClient($this->mockClient(false, 'put'));

        $this->assertFalse($adapter->put('/test', []));
    }

    public function testPutSuccess()
    {
        $adapter = new MockGuzzleAdapter('http://www.google.com');
        $adapter->setClient($this->mockClient(true, 'put'));

        $this->assertTrue($adapter->put('/test', []));
    }
}

class MockGuzzleAdapter extends GuzzleAdapter
{
    public function setClient($client)
    {
        $this->client = $client;
    }
}
