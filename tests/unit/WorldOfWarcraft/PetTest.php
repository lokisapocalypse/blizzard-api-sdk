<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Pet::class)]
class PetTest extends ServiceTestCase
{
    /**
     * Checks that a character's pets are fetched from the profile namespace of their region.
     *
     * @return void
     */
    public function testGetForCharacter(): void
    {
        $pet = $this->serviceExpecting(
            Pet::class,
            '/profile/wow/character/realm/character/collections/pets',
            ['namespace' => 'profile-kr'],
            Region::Kr,
            ['pets' => []]
        );

        $this->assertSame(['pets' => []], $pet->getForCharacter('character', 'realm', Region::Kr));
    }

    /**
     * Checks that a character's pets come from the US when no region is given, with the locale.
     *
     * @return void
     */
    public function testGetForCharacterDefaultsToUs(): void
    {
        $pet = $this->serviceExpecting(
            Pet::class,
            '/profile/wow/character/realm/character/collections/pets',
            ['namespace' => 'profile-us', 'locale' => 'en_US'],
            Region::Us,
            ['pets' => []],
            'en_US'
        );

        $this->assertSame(['pets' => []], $pet->getForCharacter('character', 'realm'));
    }
}
