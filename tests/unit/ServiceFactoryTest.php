<?php

namespace Fusani\Blizzard;

use Fusani\Blizzard\SimpleTestCase;

/**
 * @covers Fusani\Blizzard\ServiceFactory
 */
class ServiceFactoryTest extends SimpleTestCase
{
    /** @var ServiceFactory */
    protected $serviceFactory;

    public function setup()
    {
        $this->serviceFactory = new ServiceFactory();
    }

    public function testConstructInstance()
    {
        $this->assertNotNull($this->serviceFactory);
        $this->assertInstanceOf(ServiceFactory::class, $this->serviceFactory);
    }

    public function testCanBuildWorldOfWarcraftService()
    {
        $service = $this->serviceFactory->createWorldOfWarcraftService('randomkey');
        $this->assertNotNull($service);
        $this->assertInstanceOf(WorldOfWarcraft::class, $service);
    }
}
