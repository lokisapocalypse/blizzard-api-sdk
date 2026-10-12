<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Reputation::class)]
class ReputationTest extends ServiceTestCase
{
    /**
     * Checks that the reputation tier index is fetched from the static namespace of the given region.
     *
     * @return void
     */
    public function testAllReputationTiers(): void
    {
        $reputation = $this->serviceExpecting(
            Reputation::class,
            '/data/wow/reputation-tiers/index',
            ['namespace' => 'static-kr'],
            Region::Kr,
            ['reputation_tiers' => []]
        );

        $this->assertSame(['reputation_tiers' => []], $reputation->allReputationTiers(Region::Kr));
    }

    /**
     * Checks that a character's reputations are fetched from the profile endpoint in their region.
     *
     * @return void
     */
    public function testGetForCharacter(): void
    {
        $reputation = $this->serviceExpecting(
            Reputation::class,
            '/profile/wow/character/realm/character/reputations',
            ['namespace' => 'profile-eu'],
            Region::Eu,
            ['reputations' => []]
        );

        $this->assertSame(['reputations' => []], $reputation->getForCharacter('character', 'realm', Region::Eu));
    }

    /**
     * Checks that a reputation tier is fetched by id, from the US when no region is given, with the locale.
     *
     * @return void
     */
    public function testGetReputationTier(): void
    {
        $reputation = $this->serviceExpecting(
            Reputation::class,
            '/data/wow/reputation-tiers/5',
            ['namespace' => 'static-us', 'locale' => 'en_US'],
            Region::Us,
            ['id' => 5],
            'en_US'
        );

        $this->assertSame(['id' => 5], $reputation->getReputationTier(5));
    }
}
