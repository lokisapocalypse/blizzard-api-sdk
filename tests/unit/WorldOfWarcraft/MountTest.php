<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Mount::class)]
class MountTest extends ServiceTestCase
{
    /**
     * Checks that a character's mounts are fetched from the profile namespace of their region.
     *
     * @return void
     */
    public function testGetMountsForCharacter(): void
    {
        $mount = $this->serviceExpecting(
            Mount::class,
            '/profile/wow/character/realm/character/collections/mounts',
            ['namespace' => 'profile-eu'],
            Region::Eu,
            ['mounts' => []]
        );

        $this->assertSame(['mounts' => []], $mount->getMountsForCharacter('character', 'realm', Region::Eu));
    }

    /**
     * Checks that the locale is sent with the request when one is set.
     *
     * @return void
     */
    public function testGetMountsForCharacterSendsLocale(): void
    {
        $mount = $this->serviceExpecting(
            Mount::class,
            '/profile/wow/character/realm/character/collections/mounts',
            ['namespace' => 'profile-eu', 'locale' => 'de_DE'],
            Region::Eu,
            ['mounts' => []],
            'de_DE'
        );

        $this->assertSame(['mounts' => []], $mount->getMountsForCharacter('character', 'realm', Region::Eu));
    }
}
