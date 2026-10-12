<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\NotFoundException;
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
                'eu'
            )
            ->willReturn(['reputations' => []]);

        $reputation = new Reputation($api, $this->createStub(Adapter::class), 'id', 'secret', 'token');

        $this->assertSame(['reputations' => []], $reputation->getForCharacter('character', 'realm', 'eu'));
    }

    /**
     * Checks that an expired token is refreshed once and the request retried with the new token.
     *
     * @return void
     */
    public function testRefreshesExpiredToken(): void
    {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $path, array $params, ?string $token) => $token === 'new-token'
                ? ['tiers' => []]
                : throw new AuthenticationException('Unauthorized', 401));

        $oauth = $this->createMock(Adapter::class);
        $oauth->expects($this->once())
            ->method('post')
            ->with('/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => 'id',
                'client_secret' => 'secret',
            ], null)
            ->willReturn(['access_token' => 'new-token']);

        $reputation = new Reputation($api, $oauth, 'id', 'secret', 'old-token');

        $this->assertSame(
            ['tiers' => [], 'access_token' => 'new-token'],
            $reputation->allReputationTiers()
        );
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

        $oauth = $this->createStub(Adapter::class);
        $oauth->method('post')->willReturn(['access_token' => 'new-token']);

        $reputation = new Reputation($api, $oauth, 'id', 'secret', 'old-token');

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

        $oauth = $this->createMock(Adapter::class);
        $oauth->expects($this->never())->method('post');

        $reputation = new Reputation($api, $oauth, 'id', 'secret', 'token');

        $this->expectException(NotFoundException::class);

        $reputation->getReputationTier(999);
    }
}
