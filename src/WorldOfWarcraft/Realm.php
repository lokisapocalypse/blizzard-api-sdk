<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Realm endpoints.
 */
class Realm extends WorldOfWarcraft
{
    /**
     * Get all the realms for the specified region.
     *
     * @param Region $region the region
     * @return array<mixed> the realm index
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getAllRealmsByRegion(Region $region): array
    {
        $params = [
            'namespace' => $region->dynamicNamespace(),
        ];

        return $this->fetch($region, '/data/wow/realm/index', $params);
    }
}
