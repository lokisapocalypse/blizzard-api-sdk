<?php

declare(strict_types=1);

namespace Fusani\Blizzard\OAuth;

use Fusani\Blizzard\Exception\BlizzardException;

/**
 * An OAuth access token and when it expires.
 */
final class AccessToken
{
    /** Seconds before the real expiry that a token counts as expired, so it isn't sent just as it runs out. */
    public const EXPIRY_MARGIN_SECONDS = 60;

    /** @var string the bearer token */
    private readonly string $value;

    /** @var int unix timestamp when the token expires */
    private readonly int $expiresAt;

    /**
     * Build a token from its value and expiry.
     *
     * @param string $value the bearer token
     * @param int $expiresAt unix timestamp when the token expires
     */
    public function __construct(string $value, int $expiresAt)
    {
        $this->value = $value;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Build a token from the OAuth server's response, e.g. `{"access_token": "…", "expires_in": 86399}`.
     *
     * @param array<mixed> $response the decoded token response
     * @param int $now the current unix timestamp
     * @return self
     * @throws BlizzardException if the response has no access token or expiry
     */
    public static function fromResponse(array $response, int $now): self
    {
        $value = $response['access_token'] ?? null;
        $expiresIn = $response['expires_in'] ?? null;

        if (!is_string($value) || $value === '' || !is_int($expiresIn)) {
            throw new BlizzardException('The OAuth server did not return an access token and expiry.');
        }

        return new self($value, $now + $expiresIn);
    }

    /**
     * Rebuild a token from what toArray() stored, e.g. in a cache.
     *
     * @param mixed $data the stored token
     * @return self|null the token, or null if the data isn't a stored token
     */
    public static function fromArray(mixed $data): ?self
    {
        if (!is_array($data) || !is_string($data['value'] ?? null) || !is_int($data['expires_at'] ?? null)) {
            return null;
        }

        return new self($data['value'], $data['expires_at']);
    }

    /**
     * Get the bearer token.
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get when the token expires.
     *
     * @return int unix timestamp
     */
    public function expiresAt(): int
    {
        return $this->expiresAt;
    }

    /**
     * Check whether the token has expired, or is about to.
     *
     * @param int $now the current unix timestamp
     * @return bool
     */
    public function isExpired(int $now): bool
    {
        return $now >= $this->expiresAt - self::EXPIRY_MARGIN_SECONDS;
    }

    /**
     * Get how long the token can still be used, for a cache TTL.
     *
     * @param int $now the current unix timestamp
     * @return int seconds, or 0 if it has expired
     */
    public function secondsLeft(int $now): int
    {
        return max(0, $this->expiresAt - self::EXPIRY_MARGIN_SECONDS - $now);
    }

    /**
     * Get the token as an array that fromArray() can rebuild it from.
     *
     * @return array{value: string, expires_at: int}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'expires_at' => $this->expiresAt];
    }
}
