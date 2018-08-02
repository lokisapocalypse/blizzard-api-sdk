<?php

namespace Fusani\Blizzard;

use GuzzleHttp\Exception;
use Fusani\Blizzard\SimpleTestCase;

/**
 * @covers Fusani\Blizzard\WorldOfWarcraft
 */
class WorldOfWarcraftTest extends SimpleTestCase
{
    /** @var GuzzleAdapter */
    protected $adapter;

    /** @var WorldOfWarcraft */
    protected $worldOfWarcraft;

    /**
     * @return void
     */
    public function setup()
    {
        $this->adapter = $this->getMockBuilder('Fusani\Blizzard\Adapter\GuzzleAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->worldOfWarcraft = new WorldOfWarcraft($this->adapter, 'fake-key');
    }

    /**
     * @return void
     */
    public function testCanBuildObject()
    {
        $this->assertInstanceOf(WorldOfWarcraft::class, $this->worldOfWarcraft);
    }

    /**
     * @todo move into integration test and use actual key
     * @return void
     */
    public function testReputation()
    {
        $this->adapter->expects($this->once())
            ->method('get')
            ->will($this->returnValue('something'));

        $this->assertEquals('something', $this->worldOfWarcraft->reputation('character', 'realm'));
    }
}
