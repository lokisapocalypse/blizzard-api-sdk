<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\OAuth\TokenProvider;
use Fusani\Blizzard\Region;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Account::class)]
class AccountTest extends TestCase
{
    /**
     * Checks that the authorization code is exchanged for a user token, which fetches the account profile.
     *
     * @return void
     */
    public function testGetCharacters(): void
    {
        $tokens = $this->createMock(TokenProvider::class);
        $tokens->expects($this->once())
            ->method('exchangeAuthorizationCode')
            ->with(Region::Eu, 'code', 'https://example.com/callback', 'wow.profile')
            ->willReturn('user-token');
        $tokens->expects($this->never())->method('token');

        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with('/profile/user/wow', ['namespace' => 'profile-eu'], 'user-token', Region::Eu)
            ->willReturn(['wow_accounts' => []]);

        $account = new Account($api, $tokens);

        $this->assertSame(
            ['wow_accounts' => []],
            $account->getCharacters('code', 'https://example.com/callback', Region::Eu)
        );
    }

    /**
     * Checks that the account profile comes from the US when no region is given, with the locale.
     *
     * @return void
     */
    public function testGetCharactersDefaultsToUs(): void
    {
        $tokens = $this->createMock(TokenProvider::class);
        $tokens->expects($this->once())
            ->method('exchangeAuthorizationCode')
            ->with(Region::Us)
            ->willReturn('user-token');

        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with('/profile/user/wow', ['namespace' => 'profile-us', 'locale' => 'en_US'], 'user-token', Region::Us)
            ->willReturn(['wow_accounts' => []]);

        $account = new Account($api, $tokens, 'en_US');

        $this->assertSame(['wow_accounts' => []], $account->getCharacters('code', 'https://example.com/callback'));
    }

    /**
     * Checks that a rejected user token throws instead of being swapped for an app token, which can't read profiles.
     *
     * @return void
     */
    public function testGetCharactersDoesNotRetryRejectedToken(): void
    {
        $tokens = $this->createMock(TokenProvider::class);
        $tokens->method('exchangeAuthorizationCode')->willReturn('user-token');
        $tokens->expects($this->never())->method('refresh');

        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->willThrowException(new AuthenticationException('Unauthorized', 401));

        $account = new Account($api, $tokens);

        $this->expectException(AuthenticationException::class);

        $account->getCharacters('code', 'https://example.com/callback');
    }

    /**
     * Checks that a rejected authorization code throws without calling the api.
     *
     * @return void
     */
    public function testGetCharactersThrowsWhenCodeIsRejected(): void
    {
        $tokens = $this->createStub(TokenProvider::class);
        $tokens->method('exchangeAuthorizationCode')->willThrowException(new AuthenticationException('Unauthorized', 401));

        $api = $this->createMock(Adapter::class);
        $api->expects($this->never())->method('get');

        $account = new Account($api, $tokens);

        $this->expectException(AuthenticationException::class);

        $account->getCharacters('expired-code', 'https://example.com/callback');
    }
}
