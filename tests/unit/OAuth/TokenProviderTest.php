<?php

declare(strict_types=1);

namespace Fusani\Blizzard\OAuth;

use Exception;
use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheException;

#[CoversClass(TokenProvider::class)]
#[CoversClass(AccessToken::class)]
class TokenProviderTest extends TestCase
{
    /**
     * Token requests the provider sent, in order.
     *
     * @var list<array{url: string, path: string, params: array<string, string>}>
     */
    protected array $requests = [];

    /**
     * Builds a provider whose OAuth servers return the given token responses in order.
     *
     * @param list<array<mixed>> $responses the token responses to return
     * @param CacheInterface|null $cache the cache to give the provider
     * @return TokenProvider
     */
    protected function providerReturning(array $responses, ?CacheInterface $cache = null): TokenProvider
    {
        $factory = function (string $url) use (&$responses): Adapter {
            $adapter = $this->createStub(Adapter::class);
            $adapter->method('post')->willReturnCallback(
                function (string $path, array $params) use ($url, &$responses): array {
                    /** @var array<string, string> $params */
                    $this->requests[] = ['url' => $url, 'path' => $path, 'params' => $params];

                    return array_shift($responses) ?? [];
                }
            );

            return $adapter;
        };

        return new TokenProvider('id', 'secret', $cache, $factory);
    }

    /**
     * Checks that a client-credentials token is requested from the current OAuth host.
     *
     * @return void
     */
    public function testRequestsClientCredentialsToken(): void
    {
        $provider = $this->providerReturning([['access_token' => 'abc', 'expires_in' => 86399]]);

        $this->assertSame('abc', $provider->token(Region::Us));
        $this->assertSame([[
            'url' => 'https://oauth.battle.net',
            'path' => '/token',
            'params' => ['grant_type' => 'client_credentials', 'client_id' => 'id', 'client_secret' => 'secret'],
        ]], $this->requests);
    }

    /**
     * Checks that the token is reused until it expires, including by regions sharing the OAuth host.
     *
     * @return void
     */
    public function testReusesTokenAcrossSharedRegions(): void
    {
        $provider = $this->providerReturning([['access_token' => 'abc', 'expires_in' => 86399]]);

        $this->assertSame('abc', $provider->token(Region::Us));
        $this->assertSame('abc', $provider->token(Region::Eu));
        $this->assertSame('abc', $provider->token(Region::Tw));
        $this->assertCount(1, $this->requests);
    }

    /**
     * Checks that China gets its own token from its own OAuth host.
     *
     * @return void
     */
    public function testChinaHasItsOwnToken(): void
    {
        $provider = $this->providerReturning([
            ['access_token' => 'global', 'expires_in' => 86399],
            ['access_token' => 'china', 'expires_in' => 86399],
        ]);

        $this->assertSame('global', $provider->token(Region::Us));
        $this->assertSame('china', $provider->token(Region::Cn));
        $this->assertCount(2, $this->requests);
        $this->assertSame('https://oauth.battlenet.com.cn', $this->requests[1]['url']);
    }

    /**
     * Checks that an expired token is replaced on the next use instead of being sent.
     *
     * @return void
     */
    public function testReplacesExpiredToken(): void
    {
        $provider = $this->providerReturning([
            ['access_token' => 'short', 'expires_in' => AccessToken::EXPIRY_MARGIN_SECONDS],
            ['access_token' => 'long', 'expires_in' => 86399],
        ]);

        $this->assertSame('short', $provider->refresh(Region::Us));
        $this->assertSame('long', $provider->token(Region::Us));
        $this->assertCount(2, $this->requests);
    }

    /**
     * Checks that refresh() always gets a new token, even when the current one hasn't expired.
     *
     * @return void
     */
    public function testRefreshReplacesToken(): void
    {
        $provider = $this->providerReturning([
            ['access_token' => 'old', 'expires_in' => 86399],
            ['access_token' => 'new', 'expires_in' => 86399],
        ]);

        $this->assertSame('old', $provider->token(Region::Us));
        $this->assertSame('new', $provider->refresh(Region::Us));
        $this->assertSame('new', $provider->token(Region::Us));
        $this->assertCount(2, $this->requests);
    }

    /**
     * Checks that a token in the cache is used without asking the OAuth server.
     *
     * @return void
     */
    public function testUsesCachedToken(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with($this->matchesRegularExpression('/^fusani_blizzard\.token\.[0-9a-f]{64}$/'))
            ->willReturn((new AccessToken('cached', time() + 3600))->toArray());

        $provider = $this->providerReturning([], $cache);

        $this->assertSame('cached', $provider->token(Region::Us));
        $this->assertSame('cached', $provider->token(Region::Us));
        $this->assertSame([], $this->requests);
    }

    /**
     * Checks that a new token is saved in the cache until just before it expires.
     *
     * @return void
     */
    public function testStoresNewTokenInCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())
            ->method('set')
            ->with(
                $this->stringStartsWith('fusani_blizzard.token.'),
                $this->callback(fn (mixed $data): bool => is_array($data) && $data['value'] === 'abc'),
                $this->logicalAnd($this->greaterThan(3500), $this->lessThanOrEqual(3600 - 60))
            )
            ->willReturn(true);

        $provider = $this->providerReturning([['access_token' => 'abc', 'expires_in' => 3600]], $cache);

        $this->assertSame('abc', $provider->token(Region::Us));
    }

    /**
     * Checks that an authorization code is exchanged for a user token at the region's OAuth host.
     *
     * @return void
     */
    public function testExchangeAuthorizationCode(): void
    {
        $provider = $this->providerReturning([['access_token' => 'user', 'expires_in' => 86399]]);

        $token = $provider->exchangeAuthorizationCode(Region::Cn, 'code', 'https://example.com/callback', 'wow.profile');

        $this->assertSame('user', $token);
        $this->assertSame([[
            'url' => 'https://oauth.battlenet.com.cn',
            'path' => '/token',
            'params' => [
                'grant_type' => 'authorization_code',
                'code' => 'code',
                'redirect_uri' => 'https://example.com/callback',
                'scope' => 'wow.profile',
                'client_id' => 'id',
                'client_secret' => 'secret',
            ],
        ]], $this->requests);
    }

    /**
     * Checks that a response without a token throws instead of sending requests with no token.
     *
     * @return void
     */
    public function testThrowsWithoutToken(): void
    {
        $provider = $this->providerReturning([['error' => 'invalid_client']]);

        $this->expectException(BlizzardException::class);

        $provider->token(Region::Us);
    }

    /**
     * Checks that a cache that can't be read is skipped and a new token requested.
     *
     * @return void
     */
    public function testIgnoresCacheReadErrors(): void
    {
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('get')->willThrowException(new class ('bad key') extends Exception implements CacheException {
        });

        $provider = $this->providerReturning([['access_token' => 'abc', 'expires_in' => 3600]], $cache);

        $this->assertSame('abc', $provider->token(Region::Us));
    }

    /**
     * Checks that a cache that can't be written to doesn't stop the new token from being used.
     *
     * @return void
     */
    public function testIgnoresCacheWriteErrors(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->once())
            ->method('set')
            ->willThrowException(new class ('bad key') extends Exception implements CacheException {
            });

        $provider = $this->providerReturning([['access_token' => 'abc', 'expires_in' => 3600]], $cache);

        $this->assertSame('abc', $provider->token(Region::Us));
    }

    /**
     * Checks that a token that is already inside the expiry margin isn't saved in the cache.
     *
     * @return void
     */
    public function testDoesNotCacheExpiringToken(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->never())->method('set');

        $provider = $this->providerReturning([['access_token' => 'abc', 'expires_in' => 30]], $cache);

        $this->assertSame('abc', $provider->token(Region::Us));
    }
}
