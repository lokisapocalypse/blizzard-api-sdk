<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use InvalidArgumentException;

/**
 * Decides whether a failed request is tried again, and how long to wait first.
 *
 * Only a 429 and the temporary server errors (500, 502, 503 and 504) are retried. The wait doubles after each
 * failure, starting from the base delay, unless the server says how long to wait with `Retry-After`. A wait longer
 * than the maximum delay isn't worth blocking for, so the request gives up instead.
 */
final class RetryPolicy
{
    /** @var float seconds to wait before the first retry, doubled for each one after it */
    public const DEFAULT_BASE_DELAY = 0.5;

    /** @var float the longest wait, in seconds, before giving up instead */
    public const DEFAULT_MAX_DELAY = 10.0;

    /** @var list<int> statuses that may succeed if the request is sent again */
    public const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

    /** @var int how many times a failed request is sent again */
    protected readonly int $maxRetries;

    /** @var float seconds to wait before the first retry */
    protected readonly float $baseDelay;

    /** @var float the longest wait, in seconds, before giving up instead */
    protected readonly float $maxDelay;

    /**
     * Build a policy that retries a failed request up to the given number of times.
     *
     * @param int $maxRetries how many times a failed request is sent again, or 0 to never retry
     * @param float $baseDelay seconds to wait before the first retry, doubled for each one after it
     * @param float $maxDelay the longest wait, in seconds, before giving up instead
     * @throws InvalidArgumentException if the retry count is negative or a delay isn't more than 0
     */
    public function __construct(
        int $maxRetries = 0,
        float $baseDelay = self::DEFAULT_BASE_DELAY,
        float $maxDelay = self::DEFAULT_MAX_DELAY
    ) {
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('The number of retries can\'t be negative.');
        }

        if ($baseDelay <= 0 || $maxDelay <= 0) {
            throw new InvalidArgumentException('Retry delays must be more than 0 seconds.');
        }

        $this->maxRetries = $maxRetries;
        $this->baseDelay = $baseDelay;
        $this->maxDelay = $maxDelay;
    }

    /**
     * Get how many times a failed request is sent again.
     *
     * @return int
     */
    public function maxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Check whether a response status may succeed if the request is sent again.
     *
     * @param int $status the HTTP status of the failed response
     * @return bool
     */
    public function isRetryable(int $status): bool
    {
        return in_array($status, self::RETRYABLE_STATUSES, true);
    }

    /**
     * Get how long to wait before sending a failed request again, or null to give up.
     *
     * @param int $failedAttempts how many times the request has been sent and failed, including this time
     * @param int $status the HTTP status of the latest failed response
     * @param int|null $retryAfter seconds the server asked the client to wait, or null if it didn't say
     * @return float|null seconds to wait, or null if the request shouldn't be sent again
     */
    public function delayBeforeRetry(int $failedAttempts, int $status, ?int $retryAfter): ?float
    {
        if ($failedAttempts > $this->maxRetries || !$this->isRetryable($status)) {
            return null;
        }

        if ($retryAfter !== null) {
            return $retryAfter <= $this->maxDelay ? (float) $retryAfter : null;
        }

        return min($this->baseDelay * 2 ** ($failedAttempts - 1), $this->maxDelay);
    }
}
