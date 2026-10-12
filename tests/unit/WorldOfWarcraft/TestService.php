<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * A service that exposes the base class's fetch(), so its token handling can be tested on its own.
 */
class TestService extends WorldOfWarcraft
{
    /**
     * Make a get request through the base class.
     *
     * @param Region $region the region whose api the request goes to
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the query parameters that will be sent
     * @return array<mixed> the decoded response
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function request(Region $region, string $path, array $params): array
    {
        return $this->fetch($region, $path, $params);
    }

    /**
     * Build a character profile path through the base class.
     *
     * @param string $realm the realm's slug
     * @param string $character the character's name
     * @param string $endpoint what comes after the character
     * @return string
     */
    public function path(string $realm, string $character, string $endpoint): string
    {
        return $this->characterPath($realm, $character, $endpoint);
    }
}
