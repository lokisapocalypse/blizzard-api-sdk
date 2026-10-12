<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\OAuth\TokenProvider;
use Fusani\Blizzard\Region;

/**
 * Base for the World of Warcraft api services: sends requests with an app token and retries once on a 401.
 */
abstract class WorldOfWarcraft
{
    /** @var Adapter\Adapter connection to the api */
    protected Adapter\Adapter $apiAdapter;

    /** @var TokenProvider gets and reuses access tokens */
    protected TokenProvider $tokens;

    /** @var string|null locale names come back in, e.g. `en_US`, or null for every locale */
    protected ?string $locale;

    /**
     * Build the service from its api adapter and token provider.
     *
     * @param Adapter\Adapter $apiAdapter connection to the api
     * @param TokenProvider $tokens gets and reuses access tokens
     * @param string|null $locale locale names come back in, e.g. `en_US`, or null for an object of every locale
     */
    public function __construct(Adapter\Adapter $apiAdapter, TokenProvider $tokens, ?string $locale = null)
    {
        $this->apiAdapter = $apiAdapter;
        $this->tokens = $tokens;
        $this->locale = $locale;
    }

    /**
     * Build the path of a character profile endpoint, lowercasing the realm slug and name as the api requires.
     *
     * @param string $realm the realm's slug, e.g. `aerie-peak`
     * @param string $character the character's name, in any case
     * @param string $endpoint what comes after the character, e.g. `/collections/pets`
     * @return string
     */
    protected function characterPath(string $realm, string $character, string $endpoint): string
    {
        return sprintf('/profile/wow/character/%s/%s%s', mb_strtolower($realm), mb_strtolower($character), $endpoint);
    }

    /**
     * Make a get request to the api, getting a new token and retrying once if the current one is rejected.
     *
     * @param Region $region the region whose api the request goes to
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the query parameters that will be sent
     * @return array<mixed> the decoded response
     * @throws AuthenticationException if the request is still unauthorized with a new token
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    protected function fetch(Region $region, string $path, array $params): array
    {
        $params = $this->withLocale($params);

        try {
            return $this->apiAdapter->get($path, $params, $this->tokens->token($region), $region);
        } catch (AuthenticationException) {
            return $this->apiAdapter->get($path, $params, $this->tokens->refresh($region), $region);
        }
    }

    /**
     * Add the locale to a request's query parameters, if one is set.
     *
     * @param array<string, string> $params the query parameters
     * @return array<string, string>
     */
    protected function withLocale(array $params): array
    {
        if ($this->locale !== null) {
            $params['locale'] = $this->locale;
        }

        return $params;
    }
}
