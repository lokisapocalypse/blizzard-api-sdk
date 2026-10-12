<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

use Fusani\Blizzard\Adapter\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    /**
     * Checks that a client can be built from any adapter without errors.
     *
     * @return void
     */
    public function testConstruct(): void
    {
        $this->expectNotToPerformAssertions();

        $client = new Client($this->createStub(Adapter::class));
    }
}
