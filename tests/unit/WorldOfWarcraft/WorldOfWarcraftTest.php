<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\OAuth\TokenProvider;
use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorldOfWarcraft::class)]
class WorldOfWarcraftTest extends TestCase
{
    /**
     * Builds a service that exposes the base class's fetch().
     *
     * @param Adapter $api the api adapter
     * @param TokenProvider $tokens the token provider
     * @param string|null $locale the locale to send, if any
     * @return TestService
     */
    protected function service(Adapter $api, TokenProvider $tokens, ?string $locale = null): TestService
    {
        return new TestService($api, $tokens, $locale);
    }

    /**
     * Checks that a request is sent with the region's app token and its response returned unchanged.
     *
     * @return void
     */
    public function testFetchSendsToken(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with('/path', ['namespace' => 'static-eu'], 'token', Region::Eu)
            ->willReturn(['id' => 1]);

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->expects($this->once())->method('token')->with(Region::Eu)->willReturn('token');

        $this->assertSame(['id' => 1], $this->service($api, $tokens)->request(Region::Eu, '/path', [
            'namespace' => 'static-eu',
        ]));
    }

    /**
     * Checks that the locale is added to the query parameters when one is set.
     *
     * @return void
     */
    public function testFetchSendsLocale(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with('/path', ['namespace' => 'static-us', 'locale' => 'en_US'])
            ->willReturn([]);

        $tokens = $this->createStub(TokenProvider::class);
        $tokens->method('token')->willReturn('token');

        $this->service($api, $tokens, 'en_US')->request(Region::Us, '/path', ['namespace' => 'static-us']);
    }

    /**
     * Checks that a rejected token is replaced once and the request retried.
     *
     * @return void
     */
    public function testFetchRefreshesRejectedToken(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $path, array $params, ?string $token) => $token === 'new-token'
                ? ['tiers' => []]
                : throw new AuthenticationException('Unauthorized', 401));

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->expects($this->once())->method('token')->with(Region::Kr)->willReturn('old-token');
        $tokens->expects($this->once())->method('refresh')->with(Region::Kr)->willReturn('new-token');

        $this->assertSame(['tiers' => []], $this->service($api, $tokens)->request(Region::Kr, '/path', []));
    }

    /**
     * Checks that a token that is still rejected after a refresh throws instead of retrying again.
     *
     * @return void
     */
    public function testFetchThrowsWhenRefreshedTokenIsRejected(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->exactly(2))
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->method('token')->willReturn('old-token');
        $tokens->expects($this->once())->method('refresh')->willReturn('new-token');

        $this->expectException(AuthenticationException::class);

        $this->service($api, $tokens)->request(Region::Us, '/path', []);
    }

    /**
     * Checks that a 404 is passed to the caller without refreshing the token.
     *
     * @return void
     */
    public function testFetchDoesNotRetryNotFound(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->willThrowException(new NotFoundException('Not Found', 404));

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->method('token')->willReturn('token');
        $tokens->expects($this->never())->method('refresh');

        $this->expectException(NotFoundException::class);

        $this->service($api, $tokens)->request(Region::Us, '/path', []);
    }

    /**
     * Checks that any other failure is passed to the caller without refreshing the token.
     *
     * @return void
     */
    public function testFetchDoesNotRetryOtherFailures(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->willThrowException(new BlizzardException('Service Unavailable', 503));

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->method('token')->willReturn('token');
        $tokens->expects($this->never())->method('refresh');

        $this->expectExceptionObject(new BlizzardException('Service Unavailable', 503));

        $this->service($api, $tokens)->request(Region::Us, '/path', []);
    }
}
