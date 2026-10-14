<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

use Closure;
use GuzzleHttp\Client;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(ServiceFactory::class)]
class ServiceFactoryTest extends TestCase
{
    /**
     * Lists each factory method with the service class it should build.
     *
     * @return array<string, array{string, class-string}>
     */
    public static function serviceProvider(): array
    {
        return [
            'account' => ['createAccountService', WorldOfWarcraft\Account::class],
            'character media' => ['createCharacterMediaService', WorldOfWarcraft\CharacterMedia::class],
            'faction' => ['createFactionService', WorldOfWarcraft\Faction::class],
            'item' => ['createItemService', WorldOfWarcraft\Item::class],
            'mount' => ['createMountService', WorldOfWarcraft\Mount::class],
            'mythic plus' => ['createMythicPlusService', WorldOfWarcraft\MythicPlus::class],
            'pet' => ['createPetService', WorldOfWarcraft\Pet::class],
            'realm' => ['createRealmService', WorldOfWarcraft\Realm::class],
            'reputation' => ['createReputationService', WorldOfWarcraft\Reputation::class],
        ];
    }

    /**
     * Checks that each factory method builds the matching service.
     *
     * @param string $method the factory method to call
     * @param class-string $expectedClass the service class it should return
     * @return void
     */
    #[DataProvider('serviceProvider')]
    public function testCreatesService(string $method, string $expectedClass): void
    {
        $service = (new ServiceFactory('client-id', 'client-secret'))->$method();

        $this->assertInstanceOf($expectedClass, $service);
    }

    /**
     * Checks that api and OAuth requests use the default timeouts when none are given.
     *
     * @return void
     */
    public function testUsesDefaultTimeouts(): void
    {
        $factory = new ServiceFactory('client-id', 'client-secret');

        $this->assertSame([10.0, 5.0], $this->timeouts($this->apiClient($factory)));
        $this->assertSame([10.0, 5.0], $this->timeouts($this->oauthClient($factory)));
    }

    /**
     * Checks that the given timeouts reach both the api and OAuth requests.
     *
     * @return void
     */
    public function testUsesGivenTimeouts(): void
    {
        $factory = new ServiceFactory('client-id', 'client-secret', timeout: 30.0, connectTimeout: 2.0);

        $this->assertSame([30.0, 2.0], $this->timeouts($this->apiClient($factory)));
        $this->assertSame([30.0, 2.0], $this->timeouts($this->oauthClient($factory)));
    }

    /**
     * Checks that a timeout of 0, which Guzzle treats as no timeout, is rejected.
     *
     * @return void
     */
    public function testRejectsZeroTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ServiceFactory('client-id', 'client-secret', timeout: 0.0);
    }

    /**
     * Gets the Guzzle client the factory's api adapter sends requests with.
     *
     * @param ServiceFactory $factory the factory to look inside
     * @return Client
     */
    protected function apiClient(ServiceFactory $factory): Client
    {
        $adapter = new ReflectionProperty(ServiceFactory::class, 'apiAdapter')->getValue($factory);
        $this->assertInstanceOf(Adapter\GuzzleAdapter::class, $adapter);

        return $this->clientOf($adapter);
    }

    /**
     * Gets the Guzzle client the factory's token provider sends OAuth requests with.
     *
     * @param ServiceFactory $factory the factory to look inside
     * @return Client
     */
    protected function oauthClient(ServiceFactory $factory): Client
    {
        $tokens = new ReflectionProperty(ServiceFactory::class, 'tokens')->getValue($factory);
        $this->assertInstanceOf(OAuth\TokenProvider::class, $tokens);

        $adapterFactory = new ReflectionProperty(OAuth\TokenProvider::class, 'adapterFactory')->getValue($tokens);
        $this->assertInstanceOf(Closure::class, $adapterFactory);

        $adapter = $adapterFactory('https://oauth.battle.net');
        $this->assertInstanceOf(Adapter\GuzzleAdapter::class, $adapter);

        return $this->clientOf($adapter);
    }

    /**
     * Gets the Guzzle client an adapter sends requests with.
     *
     * @param Adapter\GuzzleAdapter $adapter the adapter to look inside
     * @return Client
     */
    protected function clientOf(Adapter\GuzzleAdapter $adapter): Client
    {
        $client = new ReflectionProperty(Adapter\GuzzleAdapter::class, 'client')->getValue($adapter);
        $this->assertInstanceOf(Client::class, $client);

        return $client;
    }

    /**
     * Gets a client's total and connect timeouts.
     *
     * @param Client $client the client to read
     * @return array{mixed, mixed}
     */
    protected function timeouts(Client $client): array
    {
        return [$client->getConfig('timeout'), $client->getConfig('connect_timeout')];
    }
}
