<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MythicPlus::class)]
class MythicPlusTest extends ServiceTestCase
{
    /**
     * Checks that a character's keystone profile is fetched from the profile namespace of their region.
     *
     * @return void
     */
    public function testGetProfileForCharacter(): void
    {
        $mythicPlus = $this->serviceExpecting(
            MythicPlus::class,
            '/profile/wow/character/realm/character/mythic-keystone-profile',
            ['namespace' => 'profile-eu'],
            Region::Eu,
            ['current_period' => []]
        );

        $this->assertSame(
            ['current_period' => []],
            $mythicPlus->getProfileForCharacter('character', 'realm', Region::Eu)
        );
    }

    /**
     * Checks that a keystone profile comes from the US when no region is given, with the locale.
     *
     * @return void
     */
    public function testGetProfileForCharacterDefaultsToUs(): void
    {
        $mythicPlus = $this->serviceExpecting(
            MythicPlus::class,
            '/profile/wow/character/realm/character/mythic-keystone-profile',
            ['namespace' => 'profile-us', 'locale' => 'en_US'],
            Region::Us,
            ['current_period' => []],
            'en_US'
        );

        $this->assertSame(['current_period' => []], $mythicPlus->getProfileForCharacter('character', 'realm'));
    }
}
