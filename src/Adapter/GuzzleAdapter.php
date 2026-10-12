<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Adapter that makes requests to the api over HTTP using Guzzle.
 */
class GuzzleAdapter implements Adapter
{
    /** @var Client */
    protected Client $client;

    /** @var string */
    protected string $host;

    /** @var string */
    protected string $path;

    /** @var string */
    protected string $scheme;

    /**
     * Build an adapter for the given url, keeping only its scheme, host and path.
     *
     * @param string $url the url where the requests will be made
     * @param Client|null $client the Guzzle client to send requests with, or null for a default one
     * @throws InvalidArgumentException if the url has no scheme or host
     */
    public function __construct(string $url, ?Client $client = null)
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException(sprintf('"%s" is not an absolute url.', $url));
        }

        $this->scheme = $parts['scheme'];
        $this->host = $parts['host'];
        $this->path = $parts['path'] ?? '';

        $this->client = $client ?? new Client();
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrlFor($this->host);
    }

    /**
     * {@inheritDoc}
     *
     * @throws AuthenticationException if the api responds with 401
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
     * @throws InvalidArgumentException if the region isn't a two-letter region code
     */
    public function get(string $path, array $params, ?string $accessToken, ?string $region = null): array
    {
        $host = $region === null ? $this->host : $this->hostForRegion($region);
        $url = $this->baseUrlFor($host) . $path;

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        return $this->send('GET', $url, ['headers' => $this->headersFor($accessToken)]);
    }

    /**
     * {@inheritDoc}
     *
     * @throws AuthenticationException if the server responds with 401
     * @throws NotFoundException if the server responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    public function post(string $path, array $params, ?string $accessToken): array
    {
        return $this->send('POST', $this->getBaseUrl() . $path, [
            'headers' => $this->headersFor($accessToken),
            'form_params' => $params,
        ]);
    }

    /**
     * Build the base url for a host, using the adapter's scheme and path.
     *
     * @param string $host the host to send requests to
     * @return string
     */
    protected function baseUrlFor(string $host): string
    {
        return sprintf('%s://%s%s', $this->scheme, $host, $this->path);
    }

    /**
     * Decode a JSON response body into an array.
     *
     * @param string $body the response body
     * @return array<mixed>
     * @throws BlizzardException if the body isn't a JSON object or array
     */
    protected function decode(string $body): array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BlizzardException('The response body is not valid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new BlizzardException('The response body is not a JSON object or array.');
        }

        return $decoded;
    }

    /**
     * Build the typed exception for an error response.
     *
     * @param ResponseInterface $response the error response
     * @param BadResponseException $previous the exception Guzzle threw for it
     * @return BlizzardException
     */
    protected function exceptionFor(ResponseInterface $response, BadResponseException $previous): BlizzardException
    {
        $status = $response->getStatusCode();
        $message = sprintf(
            '%s %s returned %d %s',
            $previous->getRequest()->getMethod(),
            $previous->getRequest()->getUri()->getPath(),
            $status,
            $response->getReasonPhrase()
        );

        return match ($status) {
            401 => new AuthenticationException($message, $status, $previous),
            404 => new NotFoundException($message, $status, $previous),
            default => new BlizzardException($message, $status, $previous),
        };
    }

    /**
     * Build the request headers, adding the bearer token if there is one.
     *
     * @param string|null $accessToken the access token, if any
     * @return array<string, string>
     */
    protected function headersFor(?string $accessToken): array
    {
        $headers = ['Accept' => 'application/json'];

        if ($accessToken !== null && $accessToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        return $headers;
    }

    /**
     * Swap the region at the start of the base host, e.g. `us.api.blizzard.com` to `eu.api.blizzard.com`.
     *
     * @param string $region the two-letter region code
     * @return string
     * @throws InvalidArgumentException if the region isn't a two-letter region code
     */
    protected function hostForRegion(string $region): string
    {
        if (preg_match('/^[a-z]{2}$/', $region) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a region code.', $region));
        }

        $labels = explode('.', $this->host, 2);

        return $region . '.' . ($labels[1] ?? $labels[0]);
    }

    /**
     * Send a request and decode its JSON response.
     *
     * @param string $method the HTTP method
     * @param string $url the full url to send the request to
     * @param array{headers: array<string, string>, form_params?: array<string, string>} $options the Guzzle request options
     * @return array<mixed>
     * @throws AuthenticationException if the server responds with 401
     * @throws NotFoundException if the server responds with 404
     * @throws BlizzardException if the request fails for any other reason
     */
    protected function send(string $method, string $url, array $options): array
    {
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (BadResponseException $e) {
            throw $this->exceptionFor($e->getResponse(), $e);
        } catch (GuzzleException $e) {
            throw new BlizzardException($e->getMessage(), 0, $e);
        }

        return $this->decode((string) $response->getBody());
    }
}
