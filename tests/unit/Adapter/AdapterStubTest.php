<?php

namespace Fusani\Blizzard\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterStub::class)]
class AdapterStubTest extends TestCase
{
    /** @var AdapterStub */
    protected $adapter;

    /**
     * Builds an empty stub adapter for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->adapter = new AdapterStub('https://www.example.com');
    }

    /**
     * Checks that a stub with no queued responses returns null.
     *
     * @return void
     */
    public function testReturnsNullWithoutResponses(): void
    {
        $this->assertNull($this->adapter->call('nothing', 'is', 'here'));
        $this->assertNull($this->adapter->get('/path', [], 'token'));
    }

    /**
     * Checks that queued responses come back in order, whichever method is called.
     *
     * @return void
     */
    public function testReturnsResponsesInOrder(): void
    {
        $this->adapter->addResponse('first');
        $this->adapter->addResponse('second');
        $this->adapter->addResponse('third');
        $this->adapter->addResponse('fourth');

        $this->assertSame('first', $this->adapter->call('service', 'method', []));
        $this->assertSame('second', $this->adapter->get('/path', [], 'token'));
        $this->assertSame('third', $this->adapter->post('/path', [], 'token'));
        $this->assertSame('fourth', $this->adapter->put('/path', []));
    }
}
