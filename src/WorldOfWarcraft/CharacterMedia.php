<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

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
     * @param string $region the region the realm is in, e.g. `us` or `eu`
     * @return array<mixed> the character media, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getMedia(string $character, string $realm, string $region): array
    {
        $params = [
            'namespace' => 'profile-' . $region,
        ];

        return $this->fetch("/profile/wow/character/$realm/$character/character-media", $params, $region);
    }
}
