<?php

declare(strict_types=1);

namespace Fusani\Blizzard\OAuth;

use Fusani\Blizzard\Exception\BlizzardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessToken::class)]
class AccessTokenTest extends TestCase
{
    /**
     * Checks that a token response gives the token and an expiry counted from now.
     *
     * @return void
     */
    public function testFromResponse(): void
    {
        $token = AccessToken::fromResponse(['access_token' => 'abc', 'token_type' => 'bearer', 'expires_in' => 86399], 1000);

        $this->assertSame('abc', $token->value());
        $this->assertSame(87399, $token->expiresAt());
    }

    /**
     * Lists token responses that are missing what a token needs.
     *
     * @return array<string, array{array<mixed>}>
     */
    public static function badResponseProvider(): array
    {
        return [
            'no token' => [['expires_in' => 86399]],
            'empty token' => [['access_token' => '', 'expires_in' => 86399]],
            'no expiry' => [['access_token' => 'abc']],
            'error' => [['error' => 'invalid_client']],
        ];
    }

    /**
     * Checks that a response without a token and expiry throws instead of giving a null token.
     *
     * @param array<mixed> $response the token response
     * @return void
     */
    #[DataProvider('badResponseProvider')]
    public function testFromResponseRejectsBadResponse(array $response): void
    {
        $this->expectException(BlizzardException::class);

        AccessToken::fromResponse($response, 1000);
    }

    /**
     * Checks that a token counts as expired a minute before it really expires.
     *
     * @return void
     */
    public function testIsExpiredWithMargin(): void
    {
        $token = new AccessToken('abc', 1000);

        $this->assertFalse($token->isExpired(939));
        $this->assertTrue($token->isExpired(940));
        $this->assertTrue($token->isExpired(2000));
    }

    /**
     * Checks that the seconds left stop at the margin and never go negative.
     *
     * @return void
     */
    public function testSecondsLeft(): void
    {
        $token = new AccessToken('abc', 1000);

        $this->assertSame(440, $token->secondsLeft(500));
        $this->assertSame(0, $token->secondsLeft(2000));
    }

    /**
     * Checks that a token survives a round trip through toArray() and fromArray().
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $token = AccessToken::fromArray((new AccessToken('abc', 1000))->toArray());

        $this->assertNotNull($token);
        $this->assertSame('abc', $token->value());
        $this->assertSame(1000, $token->expiresAt());
    }

    /**
     * Checks that something that isn't a stored token, like a cache miss, gives null.
     *
     * @return void
     */
    public function testFromArrayRejectsOtherData(): void
    {
        $this->assertNull(AccessToken::fromArray(null));
        $this->assertNull(AccessToken::fromArray('abc'));
        $this->assertNull(AccessToken::fromArray(['value' => 'abc']));
    }
}
