<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetryPolicy::class)]
class RetryPolicyTest extends TestCase
{
    /**
     * Checks that the default policy never retries.
     *
     * @return void
     */
    public function testDefaultNeverRetries(): void
    {
        $policy = new RetryPolicy();

        $this->assertSame(0, $policy->maxRetries());
        $this->assertNull($policy->delayBeforeRetry(1, 503, null));
    }

    /**
     * Lists statuses with whether a request that failed with them is worth sending again.
     *
     * @return array<string, array{int, bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'too many requests' => [429, true],
            'internal server error' => [500, true],
            'bad gateway' => [502, true],
            'service unavailable' => [503, true],
            'gateway timeout' => [504, true],
            'not implemented' => [501, false],
            'bad request' => [400, false],
            'unauthorized' => [401, false],
            'forbidden' => [403, false],
            'not found' => [404, false],
        ];
    }

    /**
     * Checks that only a 429 and the temporary server errors are retried.
     *
     * @param int $status the status of the failed response
     * @param bool $retryable whether the request should be sent again
     * @return void
     */
    #[DataProvider('statusProvider')]
    public function testRetriesOnlyTemporaryFailures(int $status, bool $retryable): void
    {
        $policy = new RetryPolicy(3);

        $this->assertSame($retryable, $policy->isRetryable($status));
        $this->assertSame($retryable, $policy->delayBeforeRetry(1, $status, null) !== null);
    }

    /**
     * Checks that the wait doubles after each failure and stops at the maximum delay.
     *
     * @return void
     */
    public function testBacksOffExponentially(): void
    {
        $policy = new RetryPolicy(6, 0.5, 4.0);

        $this->assertSame(0.5, $policy->delayBeforeRetry(1, 503, null));
        $this->assertSame(1.0, $policy->delayBeforeRetry(2, 503, null));
        $this->assertSame(2.0, $policy->delayBeforeRetry(3, 503, null));
        $this->assertSame(4.0, $policy->delayBeforeRetry(4, 503, null));
        $this->assertSame(4.0, $policy->delayBeforeRetry(5, 503, null));
    }

    /**
     * Checks that the request gives up once it has been retried the configured number of times.
     *
     * @return void
     */
    public function testGivesUpAfterMaxRetries(): void
    {
        $policy = new RetryPolicy(2);

        $this->assertNotNull($policy->delayBeforeRetry(2, 429, null));
        $this->assertNull($policy->delayBeforeRetry(3, 429, null));
    }

    /**
     * Checks that the server's Retry-After is used instead of the backoff when it's within the maximum delay.
     *
     * @return void
     */
    public function testUsesRetryAfter(): void
    {
        $policy = new RetryPolicy(3, 0.5, 10.0);

        $this->assertSame(3.0, $policy->delayBeforeRetry(1, 429, 3));
        $this->assertSame(0.0, $policy->delayBeforeRetry(1, 429, 0));
        $this->assertSame(10.0, $policy->delayBeforeRetry(1, 503, 10));
    }

    /**
     * Checks that a Retry-After longer than the maximum delay gives up rather than blocking for it.
     *
     * @return void
     */
    public function testGivesUpWhenRetryAfterIsTooLong(): void
    {
        $policy = new RetryPolicy(3, 0.5, 10.0);

        $this->assertNull($policy->delayBeforeRetry(1, 429, 11));
    }

    /**
     * Lists settings that make no sense for a retry policy.
     *
     * @return array<string, array{int, float, float}>
     */
    public static function invalidSettingsProvider(): array
    {
        return [
            'negative retries' => [-1, 0.5, 10.0],
            'zero base delay' => [1, 0.0, 10.0],
            'negative base delay' => [1, -0.5, 10.0],
            'zero max delay' => [1, 0.5, 0.0],
        ];
    }

    /**
     * Checks that a negative retry count or a delay of 0 or less is rejected.
     *
     * @param int $maxRetries the retry count to try
     * @param float $baseDelay the base delay to try
     * @param float $maxDelay the maximum delay to try
     * @return void
     */
    #[DataProvider('invalidSettingsProvider')]
    public function testRejectsInvalidSettings(int $maxRetries, float $baseDelay, float $maxDelay): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RetryPolicy($maxRetries, $baseDelay, $maxDelay);
    }
}
