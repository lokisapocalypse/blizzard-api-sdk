<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter\Adapter;
use Fusani\Blizzard\OAuth\TokenProvider;
use Fusani\Blizzard\Region;
use PHPUnit\Framework\TestCase;

/**
 * Base for the service tests: builds a service whose api expects exactly one request.
 */
abstract class ServiceTestCase extends TestCase
{
    /**
     * Builds a service whose api expects one get request with an app token, and returns the given response.
     *
     * @template T of WorldOfWarcraft
     * @param class-string<T> $class the service to build
     * @param string $path the path the request should go to
     * @param array<string, string> $params the query parameters the request should send
     * @param Region $region the region the request and token should be for
     * @param array<mixed> $response what the api returns
     * @param string|null $locale the locale to give the service
     * @return T
     */
    protected function serviceExpecting(
        string $class,
        string $path,
        array $params,
        Region $region,
        array $response,
        ?string $locale = null
    ): WorldOfWarcraft {
        $api = $this->createMock(Adapter::class);
        $api->expects($this->once())
            ->method('get')
            ->with($path, $params, 'token', $region)
            ->willReturn($response);

        $tokens = $this->createMock(TokenProvider::class);
        $tokens->expects($this->once())->method('token')->with($region)->willReturn('token');
        $tokens->expects($this->never())->method('refresh');

        return new $class($api, $tokens, $locale);
    }
}
