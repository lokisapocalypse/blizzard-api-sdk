<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

/**
 * Faction endpoints.
 */
class Faction extends WorldOfWarcraft
{
    /**
     * Get all the factions.
     *
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the faction index, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function allFactions(string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
        ];

        return $this->fetch('/data/wow/reputation-faction/index', $params, $region);
    }

    /**
     * Get the detailed information for a specific faction.
     *
     * @param int $id the faction id
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the faction, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getFaction(int $id, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
        ];

        return $this->fetch("/data/wow/reputation-faction/$id", $params, $region);
    }
}
