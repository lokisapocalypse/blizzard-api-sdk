<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Mount::class)]
class MountTest extends ServiceTestCase
{
    /**
     * Checks that a character's mounts are requested with the mounts field in their region.
     *
     * @return void
     */
    public function testGetMountsForCharacter(): void
    {
        $mount = $this->serviceExpecting(
            Mount::class,
            '/wow/character/realm/character',
            ['fields' => 'mounts'],
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
            '/wow/character/realm/character',
            ['fields' => 'mounts', 'locale' => 'de_DE'],
            Region::Eu,
            ['mounts' => []],
            'de_DE'
        );

        $this->assertSame(['mounts' => []], $mount->getMountsForCharacter('character', 'realm', Region::Eu));
    }
}
