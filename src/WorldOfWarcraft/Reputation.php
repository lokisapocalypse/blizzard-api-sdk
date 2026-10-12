<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

/**
 * Reputation endpoints.
 */
class Reputation extends WorldOfWarcraft
{
    /**
     * Get all reputation tiers.
     *
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the reputation tiers index, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function allReputationTiers(string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
        ];

        return $this->fetch('/data/wow/reputation-tiers/index', $params, $region);
    }

    /**
     * Get the reputation for a given character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the character's reputations, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getForCharacter(string $character, string $realm, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'profile-' . $region,
        ];

        return $this->fetch("/profile/wow/character/$realm/$character/reputations", $params, $region);
    }

    /**
     * Get a reputation tier.
     *
     * @param int $id the reputation tier id
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the reputation tier, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getReputationTier(int $id, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
        ];

        return $this->fetch("/data/wow/reputation-tiers/$id", $params, $region);
    }
}
