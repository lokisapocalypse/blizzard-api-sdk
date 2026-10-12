<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

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
     * Replace the url that request paths are appended to.
     *
     * @param string $url the new base url
     * @return static
     */
    public function changeBaseUrl(string $url): static;

    /**
     * Make a get request.
     *
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the query parameters that will be sent
     * @param string|null $accessToken the access token needed for auth
     * @return array<mixed> the decoded response, or an array with `success`, `statusCode` and `message` on failure
     */
    public function get(string $path, array $params, ?string $accessToken): array;

    /**
     * Make a post request.
     *
     * @param string $path the path where the request will be made
     * @param array<string, string> $params the form parameters that will be sent
     * @param string|null $accessToken the access token needed for auth, if any
     * @return array<mixed>|false the decoded response, or false on failure
     */
    public function post(string $path, array $params, ?string $accessToken): array|false;

    /**
     * Make a put request.
     *
     * @param string $path the path where the request will be made
     * @param array<string, mixed> $params the parameters that will be sent as a JSON body
     * @return array<mixed>|false the decoded response, or false on failure
     */
    public function put(string $path, array $params): array|false;
}
