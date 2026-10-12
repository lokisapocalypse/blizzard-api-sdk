<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Mount collection endpoints.
 */
class Mount extends WorldOfWarcraft
{
    /**
     * Get mounts collected for the given character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param Region $region the region
     * @return array<mixed> the character with its mounts
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getMountsForCharacter(string $character, string $realm, Region $region): array
    {
        $params = [
            'fields' => 'mounts',
        ];

        return $this->fetch($region, "/wow/character/$realm/$character", $params);
    }
}
