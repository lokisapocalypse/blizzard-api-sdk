<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\OAuth\TokenProvider;
use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reputation::class)]
#[CoversClass(WorldOfWarcraft::class)]
class ReputationTest extends TestCase
{
    /**
     * Checks that a character's reputations are fetched from the profile endpoint in their region.
     *
     * @return void
     */
    public function testGetForCharacter(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with(
                '/profile/wow/character/realm/character/reputations',
                ['namespace' => 'profile-eu'],
                'token',
                Region::Eu
            )
            ->willReturn(['reputations' => []]);

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->expects($this->once())->method('token')->with(Region::Eu)->willReturn('token');

        $reputation = new Reputation($api, $tokens);

        $this->assertSame(['reputations' => []], $reputation->getForCharacter('character', 'realm', Region::Eu));
    }

    /**
     * Checks that the locale is sent with every request when one is set.
     *
     * @return void
     */
    public function testSendsLocale(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with('/data/wow/reputation-tiers/5', ['namespace' => 'static-us', 'locale' => 'en_US'])
            ->willReturn(['id' => 5]);

        $tokens = $this->createStub(TokenProvider::class);
        $tokens->method('token')->willReturn('token');

        $reputation = new Reputation($api, $tokens, 'en_US');

        $this->assertSame(['id' => 5], $reputation->getReputationTier(5));
    }

    /**
     * Checks that a rejected token is replaced once and the request retried, without adding the token to the response.
     *
     * @return void
     */
    public function testRefreshesRejectedToken(): void
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

        $reputation = new Reputation($api, $tokens);

        $this->assertSame(['tiers' => []], $reputation->allReputationTiers(Region::Kr));
    }

    /**
     * Checks that a token that is still rejected after a refresh throws instead of retrying again.
     *
     * @return void
     */
    public function testThrowsWhenRefreshedTokenIsRejected(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->exactly(2))
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->method('token')->willReturn('old-token');
        $tokens->expects($this->once())->method('refresh')->willReturn('new-token');

        $reputation = new Reputation($api, $tokens);

        $this->expectException(AuthenticationException::class);

        $reputation->allReputationTiers();
    }

    /**
     * Checks that a 404 is passed to the caller without refreshing the token.
     *
     * @return void
     */
    public function testNotFoundIsNotRetried(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->willThrowException(new NotFoundException('Not Found', 404));

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->method('token')->willReturn('token');
        $tokens->expects($this->never())->method('refresh');

        $reputation = new Reputation($api, $tokens);

        $this->expectException(NotFoundException::class);

        $reputation->getReputationTier(999);
    }
}
