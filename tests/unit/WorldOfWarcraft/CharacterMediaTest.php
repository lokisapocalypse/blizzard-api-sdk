<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CharacterMedia::class)]
class CharacterMediaTest extends ServiceTestCase
{
    /**
     * Checks that a character's media is fetched from the profile namespace of their region.
     *
     * @return void
     */
    public function testGetMedia(): void
    {
        $media = $this->serviceExpecting(
            CharacterMedia::class,
            '/profile/wow/character/realm/character/character-media',
            ['namespace' => 'profile-tw'],
            Region::Tw,
            ['assets' => []]
        );

        $this->assertSame(['assets' => []], $media->getMedia('character', 'realm', Region::Tw));
    }

    /**
     * Checks that the locale is sent with the request when one is set.
     *
     * @return void
     */
    public function testGetMediaSendsLocale(): void
    {
        $media = $this->serviceExpecting(
            CharacterMedia::class,
            '/profile/wow/character/realm/character/character-media',
            ['namespace' => 'profile-us', 'locale' => 'en_US'],
            Region::Us,
            ['assets' => []],
            'en_US'
        );

        $this->assertSame(['assets' => []], $media->getMedia('character', 'realm', Region::Us));
    }
}
