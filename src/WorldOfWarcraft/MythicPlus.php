<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Mythic+ keystone profile endpoints.
 */
class MythicPlus extends WorldOfWarcraft
{
    /**
     * Get the Mythic+ keystone profile for a character.
     *
     * @param string $character the character's name, in any case
     * @param string $realm the realm's slug
     * @param Region $region the region
     * @return array<mixed> the keystone profile
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getProfileForCharacter(string $character, string $realm, Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->profileNamespace(),
        ];

        return $this->fetch($region, $this->characterPath($realm, $character, '/mythic-keystone-profile'), $params);
    }
}
