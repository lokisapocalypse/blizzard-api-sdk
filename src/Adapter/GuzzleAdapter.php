<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Exception\RateLimitException;
use Fusani\Blizzard\Region;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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
    /** @var float seconds a request may take in total before it fails */
    public const DEFAULT_TIMEOUT = 10.0;

    /** @var float seconds to wait for a connection to the server before the request fails */
    public const DEFAULT_CONNECT_TIMEOUT = 5.0;

    /** @var ClientInterface */
    protected ClientInterface $client;

    /** @var string */
    protected string $host;

    /** @var string */
    protected string $path;

    /** @var RetryPolicy decides which failed requests are sent again, and when */
    protected RetryPolicy $retryPolicy;

    /** @var string */
    protected string $scheme;

    /**
     * Build an adapter for the given url, keeping only its scheme, host and path.
     *
     * @param string $url the url where the requests will be made
     * @param ClientInterface|null $client the Guzzle client to send requests with, or null for one with the default timeouts
     * @param RetryPolicy|null $retryPolicy when to send a failed request again, or null to never retry
     * @throws InvalidArgumentException if the url has no scheme or host
     */
    public function __construct(string $url, ?ClientInterface $client = null, ?RetryPolicy $retryPolicy = null)
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException(sprintf('"%s" is not an absolute url.', $url));
        }

        $this->scheme = $parts['scheme'];
        $this->host = $parts['host'];
        $this->path = $parts['path'] ?? '';

        $this->client = $client ?? self::createClient();
        $this->retryPolicy = $retryPolicy ?? new RetryPolicy();
    }

    /**
     * Build a Guzzle client whose requests give up after the given timeouts instead of waiting forever.
     *
     * @param float $timeout seconds a request may take in total
     * @param float $connectTimeout seconds to wait for a connection to the server
     * @return Client
     * @throws InvalidArgumentException if either timeout isn't more than 0
     */
    public static function createClient(
        float $timeout = self::DEFAULT_TIMEOUT,
        float $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT
    ): Client {
        if ($timeout <= 0 || $connectTimeout <= 0) {
            throw new InvalidArgumentException('Timeouts must be more than 0 seconds.');
        }

        return new Client(['timeout' => $timeout, 'connect_timeout' => $connectTimeout]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): string
    {
        return sprintf('%s://%s%s', $this->scheme, $this->host, $this->path);
    }

    /**
     * {@inheritDoc}
     *
     * @throws AuthenticationException if the api responds with 401
     * @throws NotFoundException if the api responds with 404
     * @throws RateLimitException if the api responds with 429, after any retries
     * @throws BlizzardException if the request fails for any other reason
     */
    public function get(string $path, array $params, ?string $accessToken, ?Region $region = null): array
    {
        $baseUrl = $region === null ? $this->getBaseUrl() : $region->apiHost() . $this->path;
        $url = $baseUrl . $path;

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
     * @throws RateLimitException if the server responds with 429, after any retries
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
     * Decode an error response body, without failing when it's empty or not JSON.
     *
     * @param string $body the error response body
     * @return array<mixed>|null the decoded body, or null if it isn't a JSON object or array
     */
    protected function decodeErrorBody(string $body): ?array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Find the human-readable error in a decoded error body.
     *
     * The api sends it as `detail`, and the OAuth server as `error_description`.
     *
     * @param array<mixed>|null $body the decoded error body
     * @return string|null the error detail, or null if the body has none
     */
    protected function errorDetail(?array $body): ?string
    {
        foreach (['detail', 'error_description'] as $key) {
            if (isset($body[$key]) && is_string($body[$key]) && trim($body[$key]) !== '') {
                return trim($body[$key]);
            }
        }

        return null;
    }

    /**
     * Build the typed exception for an error response, including the server's error detail when it sent one.
     *
     * @param ResponseInterface $response the error response
     * @param BadResponseException $previous the exception Guzzle threw for it
     * @param int|null $retryAfter seconds the server asked the client to wait, or null if it didn't say
     * @return BlizzardException
     */
    protected function exceptionFor(
        ResponseInterface $response,
        BadResponseException $previous,
        ?int $retryAfter
    ): BlizzardException {
        $status = $response->getStatusCode();
        $reason = $response->getReasonPhrase();
        $message = sprintf(
            '%s %s returned %d %s',
            $previous->getRequest()->getMethod(),
            $previous->getRequest()->getUri()->getPath(),
            $status,
            $reason
        );

        $body = $this->decodeErrorBody((string) $response->getBody());
        $detail = $this->errorDetail($body);

        // The api's detail is often just the reason phrase again, e.g. "Not Found", which adds nothing.
        if ($detail !== null && strcasecmp($detail, $reason) !== 0) {
            $message .= ': ' . $detail;
        }

        return match ($status) {
            401 => new AuthenticationException($message, $status, $previous, $body),
            404 => new NotFoundException($message, $status, $previous, $body),
            429 => new RateLimitException($message, $status, $previous, $body, $retryAfter),
            default => new BlizzardException($message, $status, $previous, $body),
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
     * Read how many seconds a response asks the client to wait from its `Retry-After` header.
     *
     * The header is either a number of seconds or an HTTP date.
     *
     * @param ResponseInterface $response the error response
     * @return int|null the seconds to wait, or null if the header is missing or can't be read
     */
    protected function retryAfter(ResponseInterface $response): ?int
    {
        $header = trim($response->getHeaderLine('Retry-After'));

        if ($header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return (int) $header;
        }

        $date = strtotime($header);

        return $date === false ? null : max(0, $date - time());
    }

    /**
     * Send a request and decode its JSON response, sending it again after a temporary failure if the policy allows.
     *
     * @param string $method the HTTP method
     * @param string $url the full url to send the request to
     * @param array{headers: array<string, string>, form_params?: array<string, string>} $options the Guzzle request options
     * @return array<mixed>
     * @throws AuthenticationException if the server responds with 401
     * @throws NotFoundException if the server responds with 404
     * @throws RateLimitException if the server responds with 429, after any retries
     * @throws BlizzardException if the request fails for any other reason
     */
    protected function send(string $method, string $url, array $options): array
    {
        for ($attempt = 1;; $attempt++) {
            try {
                $response = $this->client->request($method, $url, $options);
            } catch (BadResponseException $e) {
                $response = $e->getResponse();
                $retryAfter = $this->retryAfter($response);
                $delay = $this->retryPolicy->delayBeforeRetry($attempt, $response->getStatusCode(), $retryAfter);

                if ($delay === null) {
                    throw $this->exceptionFor($response, $e, $retryAfter);
                }

                $this->sleep($delay);

                continue;
            } catch (GuzzleException $e) {
                throw new BlizzardException($e->getMessage(), 0, $e);
            }

            return $this->decode((string) $response->getBody());
        }
    }

    /**
     * Wait before sending a request again.
     *
     * @param float $seconds how long to wait
     * @return void
     */
    protected function sleep(float $seconds): void
    {
        usleep((int) round($seconds * 1_000_000));
    }
}
