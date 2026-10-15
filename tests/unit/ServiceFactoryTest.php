<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
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
     * Checks that a given client is used for both api and OAuth requests.
     *
     * @return void
     */
    public function testUsesGivenClient(): void
    {
        $client = new Client();
        $factory = new ServiceFactory('client-id', 'client-secret', httpClient: $client);

        $this->assertSame($client, $this->apiClient($factory));
        $this->assertSame($client, $this->oauthClient($factory));
    }

    /**
     * Checks that the OAuth and api requests go through the given client's middleware.
     *
     * @return void
     */
    public function testSendsRequestsThroughGivenClient(): void
    {
        /** @var list<RequestInterface> $requests */
        $requests = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], '{"access_token": "abc", "expires_in": 86399}'),
            new Response(200, [], '{"realms": []}'),
        ]));
        $stack->push(Middleware::tap(static function (RequestInterface $request) use (&$requests): void {
            $requests[] = $request;
        }));

        $factory = new ServiceFactory('client-id', 'client-secret', httpClient: new Client(['handler' => $stack]));
        $realms = $factory->createRealmService()->getAllRealmsByRegion(Region::Us);

        $this->assertSame(['realms' => []], $realms);
        $this->assertCount(2, $requests);
        $this->assertSame('POST', $requests[0]->getMethod());
        $this->assertSame('https://oauth.battle.net/token', (string) $requests[0]->getUri());
        $this->assertSame('GET', $requests[1]->getMethod());
        $this->assertStringStartsWith(
            'https://us.api.blizzard.com/data/wow/realm/index?',
            (string) $requests[1]->getUri()
        );
        $this->assertSame('Bearer abc', $requests[1]->getHeaderLine('Authorization'));
    }

    /**
     * Checks that api and OAuth requests aren't retried unless retries are turned on.
     *
     * @return void
     */
    public function testDoesNotRetryByDefault(): void
    {
        $factory = new ServiceFactory('client-id', 'client-secret');

        $this->assertSame(0, $this->retryPolicyOf($this->apiAdapter($factory))->maxRetries());
        $this->assertSame(0, $this->retryPolicyOf($this->oauthAdapter($factory))->maxRetries());
    }

    /**
     * Checks that the given number of retries reaches both the api and OAuth requests.
     *
     * @return void
     */
    public function testUsesGivenMaxRetries(): void
    {
        $factory = new ServiceFactory('client-id', 'client-secret', maxRetries: 3);

        $this->assertSame(3, $this->retryPolicyOf($this->apiAdapter($factory))->maxRetries());
        $this->assertSame(3, $this->retryPolicyOf($this->oauthAdapter($factory))->maxRetries());
    }

    /**
     * Checks that a negative number of retries is rejected.
     *
     * @return void
     */
    public function testRejectsNegativeMaxRetries(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ServiceFactory('client-id', 'client-secret', maxRetries: -1);
    }

    /**
     * Gets the Guzzle client the factory's api adapter sends requests with.
     *
     * @param ServiceFactory $factory the factory to look inside
     * @return ClientInterface
     */
    protected function apiClient(ServiceFactory $factory): ClientInterface
    {
        return $this->clientOf($this->apiAdapter($factory));
    }

    /**
     * Gets the factory's api adapter.
     *
     * @param ServiceFactory $factory the factory to look inside
     * @return Adapter\GuzzleAdapter
     */
    protected function apiAdapter(ServiceFactory $factory): Adapter\GuzzleAdapter
    {
        $adapter = new ReflectionProperty(ServiceFactory::class, 'apiAdapter')->getValue($factory);
        $this->assertInstanceOf(Adapter\GuzzleAdapter::class, $adapter);

        return $adapter;
    }

    /**
     * Gets the Guzzle client the factory's token provider sends OAuth requests with.
     *
     * @param ServiceFactory $factory the factory to look inside
     * @return ClientInterface
     */
    protected function oauthClient(ServiceFactory $factory): ClientInterface
    {
        return $this->clientOf($this->oauthAdapter($factory));
    }

    /**
     * Gets an adapter built by the factory's token provider for the OAuth server.
     *
     * @param ServiceFactory $factory the factory to look inside
     * @return Adapter\GuzzleAdapter
     */
    protected function oauthAdapter(ServiceFactory $factory): Adapter\GuzzleAdapter
    {
        $tokens = new ReflectionProperty(ServiceFactory::class, 'tokens')->getValue($factory);
        $this->assertInstanceOf(OAuth\TokenProvider::class, $tokens);

        $adapterFactory = new ReflectionProperty(OAuth\TokenProvider::class, 'adapterFactory')->getValue($tokens);
        $this->assertInstanceOf(Closure::class, $adapterFactory);

        $adapter = $adapterFactory('https://oauth.battle.net');
        $this->assertInstanceOf(Adapter\GuzzleAdapter::class, $adapter);

        return $adapter;
    }

    /**
     * Gets the Guzzle client an adapter sends requests with.
     *
     * @param Adapter\GuzzleAdapter $adapter the adapter to look inside
     * @return ClientInterface
     */
    protected function clientOf(Adapter\GuzzleAdapter $adapter): ClientInterface
    {
        $client = new ReflectionProperty(Adapter\GuzzleAdapter::class, 'client')->getValue($adapter);
        $this->assertInstanceOf(ClientInterface::class, $client);

        return $client;
    }

    /**
     * Gets the retry policy an adapter sends requests with.
     *
     * @param Adapter\GuzzleAdapter $adapter the adapter to look inside
     * @return Adapter\RetryPolicy
     */
    protected function retryPolicyOf(Adapter\GuzzleAdapter $adapter): Adapter\RetryPolicy
    {
        $policy = new ReflectionProperty(Adapter\GuzzleAdapter::class, 'retryPolicy')->getValue($adapter);
        $this->assertInstanceOf(Adapter\RetryPolicy::class, $policy);

        return $policy;
    }

    /**
     * Gets a client's total and connect timeouts.
     *
     * @param ClientInterface $client the client to read
     * @return array{mixed, mixed}
     */
    protected function timeouts(ClientInterface $client): array
    {
        $this->assertInstanceOf(Client::class, $client);

        return [$client->getConfig('timeout'), $client->getConfig('connect_timeout')];
    }
}
