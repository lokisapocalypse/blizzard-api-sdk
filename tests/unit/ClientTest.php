<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\Adapter\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    /**
     * Checks that a client can be built from any adapter.
     *
     * @return void
     */
    public function testConstruct(): void
    {
        $client = new Client($this->createStub(Adapter::class));

        $this->assertInstanceOf(Client::class, $client);
    }
}
