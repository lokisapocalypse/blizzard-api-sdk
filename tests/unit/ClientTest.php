<?php

namespace Fusani\Fusani;

use Fusani\Fusani\SimpleTestCase;

/**
 * @covers Fusani\Fusani\Client
 */
class ClientTest extends SimpleTestCase
{
    public function testConstruct()
    {
        $adapter = $this->getMockBuilder('Fusani\Fusani\Adapter\Adapter')
            ->disableOriginalConstructor()
            ->getMock();
        $client = new Client($adapter);

        $this->assertAttributeInstanceOf('Fusani\Fusani\Adapter\Adapter', 'adapter', $client);
    }
}
