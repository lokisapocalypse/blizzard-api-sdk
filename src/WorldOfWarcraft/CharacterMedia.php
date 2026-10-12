<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Character media (avatar and render images) endpoints.
 */
class CharacterMedia extends WorldOfWarcraft
{
    /**
     * Get all profile images for the provided character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param Region $region the region the realm is in
     * @return array<mixed> the character media
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getMedia(string $character, string $realm, Region $region): array
    {
        $params = [
            'namespace' => $region->profileNamespace(),
        ];

        return $this->fetch($region, "/profile/wow/character/$realm/$character/character-media", $params);
    }
}
