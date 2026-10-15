<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when a request to the Blizzard api or OAuth server fails.
 *
 * The exception code is the HTTP status code when the server responded, or 0 when it didn't.
 */
class BlizzardException extends RuntimeException
{
    /** @var array<mixed>|null the decoded JSON body of the error response, if there was one */
    protected ?array $responseBody;

    /**
     * Build the exception, keeping the decoded error response body if there is one.
     *
     * @param string $message the exception message
     * @param int $code the HTTP status code, or 0 if the server didn't respond
     * @param Throwable|null $previous the exception that caused this one
     * @param array<mixed>|null $responseBody the decoded JSON body of the error response, or null if there wasn't one
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?array $responseBody = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->responseBody = $responseBody;
    }

    /**
     * Get the decoded JSON body of the error response, e.g. `['code' => 404, 'type' => '...', 'detail' => '...']`.
     *
     * @return array<mixed>|null the body, or null if the server didn't respond or its body wasn't a JSON object or array
     */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
