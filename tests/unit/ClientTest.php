<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\SimpleTestCase;

/**
 * @covers Fusani\Blizzard\Client
 */
class ClientTest extends SimpleTestCase
{
    public function testConstruct()
    {
        $adapter = $this->getMockBuilder('Fusani\Blizzard\Adapter\Adapter')
            ->disableOriginalConstructor()
            ->getMock();
        $client = new Client($adapter);

        $this->assertAttributeInstanceOf('Fusani\Blizzard\Adapter\Adapter', 'adapter', $client);
    }
}
