<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Faction endpoints.
 */
class Faction extends WorldOfWarcraft
{
    /**
     * Get all the factions.
     *
     * @param Region $region the region
     * @return array<mixed> the faction index
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function allFactions(Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->staticNamespace(),
        ];

        return $this->fetch($region, '/data/wow/reputation-faction/index', $params);
    }

    /**
     * Get the detailed information for a specific faction.
     *
     * @param int $id the faction id
     * @param Region $region the region
     * @return array<mixed> the faction
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getFaction(int $id, Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->staticNamespace(),
        ];

        return $this->fetch($region, "/data/wow/reputation-faction/$id", $params);
    }
}
