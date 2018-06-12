<?php

namespace Blizzard\WorldOfWarcraft;

use Blizzard\WorldOfWarcraft\SimpleTestCase;

/**
 * @covers Blizzard\WorldOfWarcraft\Client
 */
class ClientTest extends SimpleTestCase
{
    public function testConstruct()
    {
        $adapter = $this->getMockBuilder('Blizzard\WorldOfWarcraft\Adapter\Adapter')
            ->disableOriginalConstructor()
            ->getMock();
        $client = new Client($adapter);

        $this->assertAttributeInstanceOf('Blizzard\WorldOfWarcraft\Adapter\Adapter', 'adapter', $client);
    }
}
