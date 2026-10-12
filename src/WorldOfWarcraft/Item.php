<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * Item endpoints.
 */
class Item extends WorldOfWarcraft
{
    /**
     * Get the item by the associated id.
     *
     * @param int $id the item id
     * @param Region $region the region
     * @return array<mixed> the item
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getById(int $id, Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->staticNamespace(),
        ];

        return $this->fetch($region, "/data/wow/item/$id", $params);
    }

    /**
     * Get the icon of the associated item id.
     *
     * @param int $id the item id
     * @param Region $region the region
     * @return array<mixed> the item media
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getIcon(int $id, Region $region = Region::Us): array
    {
        $params = [
            'namespace' => $region->staticNamespace(),
        ];

        return $this->fetch($region, "/data/wow/media/item/$id", $params);
    }
}
