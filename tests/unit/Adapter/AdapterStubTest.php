<?php

namespace Fusani\Fusani\Adapter;

use GuzzleHttp\Exception;
use Fusani\Fusani\SimpleTestCase;

/**
 * @covers Fusani\Fusani\Adapter\AdapterStub
 */
class AdapterStubTest extends SimpleTestCase
{
    protected $adapter;

    public function setup()
    {
        $this->adapter = new AdapterStub('http://www.google.com');
    }

    public function testCall()
    {
        $this->adapter->addResponse('hai');
        $this->assertEquals('hai', $this->adapter->call('what', 'the', 'ish'));
    }

    public function testConstruct()
    {
        $this->assertNull($this->adapter->call('nothing', 'is', 'here'));
    }

    public function testGetFailure()
    {
        try {
            $this->adapter->get('wont', 'work');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testPostFailure()
    {
        try {
            $this->adapter->post('wont', 'work');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testPutFailure()
    {
        try {
            $this->adapter->put('wont', 'work');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }
}
