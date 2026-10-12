<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Realm::class)]
class RealmTest extends ServiceTestCase
{
    /**
     * Checks that the realm index is fetched from the dynamic namespace of the given region.
     *
     * @return void
     */
    public function testGetAllRealmsByRegion(): void
    {
        $realm = $this->serviceExpecting(
            Realm::class,
            '/data/wow/realm/index',
            ['namespace' => 'dynamic-cn'],
            Region::Cn,
            ['realms' => []]
        );

        $this->assertSame(['realms' => []], $realm->getAllRealmsByRegion(Region::Cn));
    }

    /**
     * Checks that the locale is sent with the request when one is set.
     *
     * @return void
     */
    public function testGetAllRealmsByRegionSendsLocale(): void
    {
        $realm = $this->serviceExpecting(
            Realm::class,
            '/data/wow/realm/index',
            ['namespace' => 'dynamic-us', 'locale' => 'en_US'],
            Region::Us,
            ['realms' => []],
            'en_US'
        );

        $this->assertSame(['realms' => []], $realm->getAllRealmsByRegion(Region::Us));
    }
}
