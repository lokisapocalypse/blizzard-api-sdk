<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reputation::class)]
#[CoversClass(WorldOfWarcraft::class)]
class ReputationTest extends TestCase
{
    /**
     * Checks that a character's reputations are fetched from the profile endpoint.
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
                ['namespace' => 'profile-us', 'region' => 'us'],
                'token'
            )
            ->willReturn(['reputations' => []]);

        $reputation = new Reputation($api, $this->createStub(Adapter::class), 'id', 'secret', 'token');

        $this->assertSame(['reputations' => []], $reputation->getForCharacter('character', 'realm'));
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
                : ['success' => false, 'statusCode' => 401, 'message' => 'Unauthorized']);

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
}
