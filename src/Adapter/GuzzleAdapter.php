<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use InvalidArgumentException;

/**
 * Adapter that makes requests to the api over HTTP using Guzzle.
 */
class GuzzleAdapter implements Adapter
{
    /** @var string */
    protected string $baseUrl;

    /** @var Client */
    protected Client $client;

    /**
     * Build an adapter for the given url, keeping only its scheme, host and path.
     *
     * @param string $url the url where the requests will be made
     * @param Client|null $client the Guzzle client to send requests with, or null for a default one
     * @throws InvalidArgumentException if the url has no scheme or host
     */
    public function __construct(string $url, ?Client $client = null)
    {
        // break the url down
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException(sprintf('"%s" is not an absolute url.', $url));
        }

        // build the base url
        $this->baseUrl = sprintf('%s://%s%s', $parts['scheme'], $parts['host'], $parts['path'] ?? '');

        // build the guzzle client
        $this->client = $client ?? new Client();
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function changeBaseUrl(string $url): static
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\GuzzleException if the request can't be sent, e.g. on a connection failure
     */
    public function get(string $path, array $params, ?string $accessToken): array
    {
        $url = $path . '?' . http_build_query($params);

        // if a region is set, override the base url
        if (!empty($params['region'])) {
            $this->baseUrl = str_replace('us.', $params['region'] . '.', $this->baseUrl);
        }

        $config = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ];

        try {
            $response = $this->client->get($this->baseUrl . $url, $config);

            return $this->decode($response->getBody()->getContents());
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            $response = $e->getResponse();

            return [
                'success' => false,
                'statusCode' => $response->getStatusCode(),
                'message' => $response->getReasonPhrase(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\GuzzleException if the request can't be sent, e.g. on a connection failure
     */
    public function post(string $path, array $params, ?string $accessToken): array|false
    {
        $config = [];

        if (!empty($accessToken)) {
            $config = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ];
        }

        try {
            $response = $this->client->post($this->baseUrl . $path, ['form_params' => $params], $config);
            $results = $this->decode($response->getBody()->getContents());
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            return false;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\GuzzleException if the request can't be sent, e.g. on a connection failure
     */
    public function put(string $path, array $params): array|false
    {
        try {
            $request = $this->client->put(
                $path,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => json_encode($params),
                ]
            );
            $response = $this->client->send($request);

            $results = $this->decode($response->getBody()->getContents());
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            return false;
        }

        return $results;
    }

    /**
     * Decode a JSON response body into an array.
     *
     * @param string $body the response body
     * @return array<mixed> the decoded body, or an empty array if it isn't a JSON object or array
     */
    protected function decode(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
