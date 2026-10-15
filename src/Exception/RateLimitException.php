<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Exception;

use Throwable;

/**
 * Thrown when the api or OAuth server responds with 429 because the client sent too many requests.
 *
 * Blizzard allows 100 requests a second and 36,000 an hour for each client.
 */
class RateLimitException extends BlizzardException
{
    /** @var int|null seconds the server asked the client to wait before trying again, if it said */
    protected ?int $retryAfter;

    /**
     * Build the exception, keeping how long the server asked the client to wait.
     *
     * @param string $message the exception message
     * @param int $code the HTTP status code
     * @param Throwable|null $previous the exception that caused this one
     * @param array<mixed>|null $responseBody the decoded JSON body of the error response, or null if there wasn't one
     * @param int|null $retryAfter seconds to wait before trying again, from the `Retry-After` header, or null if it
     *     wasn't sent
     */
    public function __construct(
        string $message = '',
        int $code = 429,
        ?Throwable $previous = null,
        ?array $responseBody = null,
        ?int $retryAfter = null
    ) {
        parent::__construct($message, $code, $previous, $responseBody);

        $this->retryAfter = $retryAfter;
    }

    /**
     * Get how many seconds the server asked the client to wait before trying again.
     *
     * @return int|null the seconds from the `Retry-After` header, or null if the server didn't send one
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
