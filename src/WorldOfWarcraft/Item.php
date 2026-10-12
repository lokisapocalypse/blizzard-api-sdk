<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

/**
 * Item endpoints (US only).
 */
class Item extends WorldOfWarcraft
{
    /**
     * Get the item by the associated id.
     *
     * @param int $id the item id
     * @return array<mixed> the item, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getById(int $id): array
    {
        $params = [
            'namespace' => 'static-us',
        ];

        return $this->fetch("/data/wow/item/$id", $params, 'us');
    }

    /**
     * Get the icon of the associated item id.
     *
     * @param int $id the item id
     * @return array<mixed> the item media, with `access_token` added if the token was refreshed
     * @throws AuthenticationException if the request is still unauthorized after refreshing the token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function getIcon(int $id): array
    {
        $params = [
            'namespace' => 'static-us',
        ];

        return $this->fetch("/data/wow/media/item/$id", $params, 'us');
    }
}
