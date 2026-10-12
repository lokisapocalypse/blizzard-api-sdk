<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

/**
 * Realm endpoints.
 */
class Realm extends WorldOfWarcraft
{
    /**
     * Get all the realms for the specified region.
     *
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the realm index, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getAllRealmsByRegion(string $region): array
    {
        $region = strtolower($region);

        $params = [
            'namespace' => 'dynamic-' . $region,
        ];

        return $this->fetch('/data/wow/realm/index', $params, $region);
    }
}
