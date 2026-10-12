<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterStub::class)]
class AdapterStubTest extends TestCase
{
    /** @var AdapterStub */
    protected AdapterStub $adapter;

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
     * Checks that a stub with no queued responses returns an empty array.
     *
     * @return void
     */
    public function testReturnsEmptyArrayWithoutResponses(): void
    {
        $this->assertSame([], $this->adapter->call('nothing', 'is', []));
        $this->assertSame([], $this->adapter->get('/path', [], 'token'));
    }

    /**
     * Checks that queued responses come back in order, whichever method is called.
     *
     * @return void
     */
    public function testReturnsResponsesInOrder(): void
    {
        $this->adapter->addResponse(['first']);
        $this->adapter->addResponse(['second']);
        $this->adapter->addResponse(['third']);

        $this->assertSame(['first'], $this->adapter->call('service', 'method', []));
        $this->assertSame(['second'], $this->adapter->get('/path', [], 'token'));
        $this->assertSame(['third'], $this->adapter->post('/path', [], 'token'));
    }

    /**
     * Checks that the stub keeps the url it was built with.
     *
     * @return void
     */
    public function testGetBaseUrl(): void
    {
        $this->assertSame('https://www.example.com', $this->adapter->getBaseUrl());
    }
}
