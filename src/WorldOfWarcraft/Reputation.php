<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Reputation endpoints.
 */
class Reputation extends WorldOfWarcraft
{
    /**
     * Get all reputation tiers.
     *
     * @param Region $region the region
     * @return array<mixed> the reputation tiers index
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function allReputationTiers(Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->staticNamespace(),
        ];

        return $this->fetch($region, '/data/wow/reputation-tiers/index', $params);
    }

    /**
     * Get the reputation for a given character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param Region $region the region
     * @return array<mixed> the character's reputations
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getForCharacter(string $character, string $realm, Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->profileNamespace(),
        ];

        return $this->fetch($region, "/profile/wow/character/$realm/$character/reputations", $params);
    }

    /**
     * Get a reputation tier.
     *
     * @param int $id the reputation tier id
     * @param Region $region the region
     * @return array<mixed> the reputation tier
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getReputationTier(int $id, Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->staticNamespace(),
        ];

        return $this->fetch($region, "/data/wow/reputation-tiers/$id", $params);
    }
}
