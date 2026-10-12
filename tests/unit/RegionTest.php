<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Region::class)]
class RegionTest extends TestCase
{
    /**
     * Lists each region with its api and OAuth hosts.
     *
     * @return array<string, array{Region, string, string}>
     */
    public static function hostProvider(): array
    {
        return [
            'us' => [Region::Us, 'https://us.api.blizzard.com', 'https://oauth.battle.net'],
            'eu' => [Region::Eu, 'https://eu.api.blizzard.com', 'https://oauth.battle.net'],
            'kr' => [Region::Kr, 'https://kr.api.blizzard.com', 'https://oauth.battle.net'],
            'tw' => [Region::Tw, 'https://tw.api.blizzard.com', 'https://oauth.battle.net'],
            'cn' => [Region::Cn, 'https://gateway.battlenet.com.cn', 'https://oauth.battlenet.com.cn'],
        ];
    }

    /**
     * Checks that each region gives its own api host and the OAuth host that issues its tokens.
     *
     * @param Region $region the region to check
     * @param string $apiHost the api host it should give
     * @param string $oauthHost the OAuth host it should give
     * @return void
     */
    #[DataProvider('hostProvider')]
    public function testHosts(Region $region, string $apiHost, string $oauthHost): void
    {
        $this->assertSame($apiHost, $region->apiHost());
        $this->assertSame($oauthHost, $region->oauthHost());
    }

    /**
     * Checks that the namespaces end in the region code.
     *
     * @return void
     */
    public function testNamespaces(): void
    {
        $this->assertSame('profile-eu', Region::Eu->profileNamespace());
        $this->assertSame('static-kr', Region::Kr->staticNamespace());
        $this->assertSame('dynamic-cn', Region::Cn->dynamicNamespace());
    }
}
