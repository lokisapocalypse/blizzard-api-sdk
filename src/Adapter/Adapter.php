<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;

/**
 * This allows us to build several different types of adapters with
 * common methods.
 */
interface Adapter
{
    /**
     * Build an adapter by specifying a url where requests will be made.
     *
     * @param string $url the url where the requests will be made
     */
    public function __construct(string $url);

    /**
     * Get the url that request paths are appended to.
     *
     * @return string
     */
    public function getBaseUrl(): string;

    /**
     * Make a get request.
     *
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the query parameters that will be sent
     * @param string|null $accessToken the access token needed for auth
     * @param Region|null $region the region whose api host the request goes to, or null for the base url's
     * @return array<mixed> the decoded response
     * @throws AuthenticationException if the api responds with 401
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function get(string $path, array $params, ?string $accessToken, ?Region $region = null): array;

    /**
     * Make a post request.
     *
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the form parameters that will be sent
     * @param string|null $accessToken the access token needed for auth, if any
     * @return array<mixed> the decoded response
     * @throws AuthenticationException if the server responds with 401
     * @throws NotFoundException if the server responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function post(string $path, array $params, ?string $accessToken): array;
}
