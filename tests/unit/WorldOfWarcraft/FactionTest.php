<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Faction::class)]
class FactionTest extends ServiceTestCase
{
    /**
     * Checks that the faction index is fetched from the static namespace of the given region.
     *
     * @return void
     */
    public function testAllFactions(): void
    {
        $faction = $this->serviceExpecting(
            Faction::class,
            '/data/wow/reputation-faction/index',
            ['namespace' => 'static-eu'],
            Region::Eu,
            ['factions' => []]
        );

        $this->assertSame(['factions' => []], $faction->allFactions(Region::Eu));
    }

    /**
     * Checks that the faction index comes from the US when no region is given.
     *
     * @return void
     */
    public function testAllFactionsDefaultsToUs(): void
    {
        $faction = $this->serviceExpecting(
            Faction::class,
            '/data/wow/reputation-faction/index',
            ['namespace' => 'static-us'],
            Region::Us,
            ['factions' => []]
        );

        $this->assertSame(['factions' => []], $faction->allFactions());
    }

    /**
     * Checks that a faction is fetched by id, from the US when no region is given, with the locale.
     *
     * @return void
     */
    public function testGetFaction(): void
    {
        $faction = $this->serviceExpecting(
            Faction::class,
            '/data/wow/reputation-faction/2510',
            ['namespace' => 'static-us', 'locale' => 'en_GB'],
            Region::Us,
            ['id' => 2510],
            'en_GB'
        );

        $this->assertSame(['id' => 2510], $faction->getFaction(2510));
    }

    /**
     * Checks that a faction is fetched from the given region.
     *
     * @return void
     */
    public function testGetFactionInRegion(): void
    {
        $faction = $this->serviceExpecting(
            Faction::class,
            '/data/wow/reputation-faction/2510',
            ['namespace' => 'static-tw'],
            Region::Tw,
            ['id' => 2510]
        );

        $this->assertSame(['id' => 2510], $faction->getFaction(2510, Region::Tw));
    }
}
