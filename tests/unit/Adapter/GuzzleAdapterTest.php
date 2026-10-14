<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Region;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use Throwable;

#[CoversClass(GuzzleAdapter::class)]
class GuzzleAdapterTest extends TestCase
{
    /**
     * Requests the adapter sent, in order.
     *
     * @var list<RequestInterface>
     */
    protected array $requests = [];

    /**
     * Builds an adapter whose Guzzle client returns the given responses instead of calling the network.
     *
     * @param list<ResponseInterface|Throwable> $responses the responses to return, or exceptions to throw, in order
     * @param string $url the base url for the adapter
     * @return GuzzleAdapter
     */
    protected function adapterReturning(array $responses, string $url = 'https://us.api.blizzard.com'): GuzzleAdapter
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            $this->requests[] = $request;

            return $request;
        }));

        return new GuzzleAdapter($url, new Client(['handler' => $stack]));
    }

    /**
     * Gets a request the adapter sent, in the order they were sent.
     *
     * @param int $index the position of the request, starting at 0
     * @return RequestInterface
     */
    protected function sentRequest(int $index): RequestInterface
    {
        $this->assertArrayHasKey($index, $this->requests);

        return $this->requests[$index];
    }

    /**
     * Checks that the base url keeps the scheme, host and path but drops the query string.
     *
     * @return void
     */
    public function testConstructBuildsBaseUrl(): void
    {
        $adapter = new GuzzleAdapter('https://us.api.blizzard.com/data?ignored=1');

        $this->assertSame('https://us.api.blizzard.com/data', $adapter->getBaseUrl());
    }

    /**
     * Checks that an adapter built without a client gets one with the default timeouts.
     *
     * @return void
     */
    public function testConstructUsesDefaultTimeouts(): void
    {
        $adapter = new GuzzleAdapter('https://example.com');
        $client = new ReflectionProperty(GuzzleAdapter::class, 'client')->getValue($adapter);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame(GuzzleAdapter::DEFAULT_TIMEOUT, $client->getConfig('timeout'));
        $this->assertSame(GuzzleAdapter::DEFAULT_CONNECT_TIMEOUT, $client->getConfig('connect_timeout'));
    }

    /**
     * Checks that a client can be built with other timeouts.
     *
     * @return void
     */
    public function testCreateClientUsesGivenTimeouts(): void
    {
        $client = GuzzleAdapter::createClient(30.0, 2.5);

        $this->assertSame(30.0, $client->getConfig('timeout'));
        $this->assertSame(2.5, $client->getConfig('connect_timeout'));
    }

    /**
     * Lists timeouts that would let a request wait forever, or make no sense.
     *
     * @return array<string, array{float, float}>
     */
    public static function invalidTimeoutProvider(): array
    {
        return [
            'zero timeout' => [0.0, 5.0],
            'negative timeout' => [-1.0, 5.0],
            'zero connect timeout' => [10.0, 0.0],
            'negative connect timeout' => [10.0, -1.0],
        ];
    }

    /**
     * Checks that a timeout of 0 or less is rejected, since Guzzle treats 0 as no timeout at all.
     *
     * @param float $timeout the total timeout to try
     * @param float $connectTimeout the connect timeout to try
     * @return void
     */
    #[DataProvider('invalidTimeoutProvider')]
    public function testCreateClientRejectsInvalidTimeouts(float $timeout, float $connectTimeout): void
    {
        $this->expectException(InvalidArgumentException::class);

        GuzzleAdapter::createClient($timeout, $connectTimeout);
    }

    /**
     * Checks that a relative url is rejected.
     *
     * @return void
     */
    public function testConstructRejectsRelativeUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new GuzzleAdapter('/data');
    }

    /**
     * Checks that a successful get sends the bearer token and decodes the JSON body.
     *
     * @return void
     */
    public function testGetSuccess(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{"name":"Ashbringer"}')]);

        $this->assertSame(['name' => 'Ashbringer'], $adapter->get('/test', ['locale' => 'en_US'], 'token'));

        $request = $this->sentRequest(0);
        $this->assertSame('https://us.api.blizzard.com/test?locale=en_US', (string) $request->getUri());
        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
    }

    /**
     * Checks that a get without a token sends no Authorization header.
     *
     * @return void
     */
    public function testGetWithoutToken(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{}')]);

        $adapter->get('/test', [], null);

        $this->assertFalse($this->sentRequest(0)->hasHeader('Authorization'));
    }

    /**
     * Checks that a region sends the request to that region's host without adding it to the query.
     *
     * @return void
     */
    public function testGetUsesRegionHost(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{}')]);

        $adapter->get('/test', ['namespace' => 'static-eu'], 'token', Region::Eu);

        $uri = $this->sentRequest(0)->getUri();
        $this->assertSame('eu.api.blizzard.com', $uri->getHost());
        $this->assertSame('namespace=static-eu', $uri->getQuery());
    }

    /**
     * Checks that a regional request doesn't change the host used by later requests.
     *
     * @return void
     */
    public function testRegionDoesNotPersist(): void
    {
        $adapter = $this->adapterReturning([
            new Response(200, [], '{}'),
            new Response(200, [], '{}'),
            new Response(200, [], '{}'),
        ]);

        $adapter->get('/test', [], 'token', Region::Eu);
        $adapter->get('/test', [], 'token', Region::Us);
        $adapter->get('/test', [], 'token');

        $this->assertSame('eu.api.blizzard.com', $this->sentRequest(0)->getUri()->getHost());
        $this->assertSame('us.api.blizzard.com', $this->sentRequest(1)->getUri()->getHost());
        $this->assertSame('us.api.blizzard.com', $this->sentRequest(2)->getUri()->getHost());
        $this->assertSame('https://us.api.blizzard.com', $adapter->getBaseUrl());
    }

    /**
     * Checks that China's requests go to its own gateway rather than a `cn.api.blizzard.com` host.
     *
     * @return void
     */
    public function testGetUsesChinaGateway(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{}')]);

        $adapter->get('/test', [], 'token', Region::Cn);

        $this->assertSame('gateway.battlenet.com.cn', $this->sentRequest(0)->getUri()->getHost());
    }

    /**
     * Lists error statuses with the exception each should throw.
     *
     * @return array<string, array{int, class-string<BlizzardException>}>
     */
    public static function errorProvider(): array
    {
        return [
            'unauthorized' => [401, AuthenticationException::class],
            'not found' => [404, NotFoundException::class],
            'bad request' => [400, BlizzardException::class],
            'too many requests' => [429, BlizzardException::class],
            'server error' => [500, BlizzardException::class],
        ];
    }

    /**
     * Checks that an error response from a get throws the matching typed exception with the status code.
     *
     * @param int $status the HTTP status the api responds with
     * @param class-string<BlizzardException> $expected the exception class that should be thrown
     * @return void
     */
    #[DataProvider('errorProvider')]
    public function testGetThrowsOnErrorResponse(int $status, string $expected): void
    {
        $adapter = $this->adapterReturning([new Response($status)]);

        try {
            $adapter->get('/test', [], 'token');
            $this->fail('Expected an exception.');
        } catch (BlizzardException $e) {
            $this->assertSame($expected, $e::class);
            $this->assertSame($status, $e->getCode());
        }
    }

    /**
     * Checks that a connection failure throws a BlizzardException with no status code.
     *
     * @return void
     */
    public function testGetThrowsOnConnectionFailure(): void
    {
        $adapter = $this->adapterReturning([
            new ConnectException('Could not resolve host', new Request('GET', '/test')),
        ]);

        $this->expectException(BlizzardException::class);
        $this->expectExceptionCode(0);

        $adapter->get('/test', [], 'token');
    }

    /**
     * Checks that a timed-out request throws a BlizzardException with no status code.
     *
     * @return void
     */
    public function testGetThrowsOnTimeout(): void
    {
        $adapter = $this->adapterReturning([
            new ConnectException('Operation timed out after 10001 milliseconds', new Request('GET', '/test')),
        ]);

        $this->expectException(BlizzardException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('timed out');

        $adapter->get('/test', [], 'token');
    }

    /**
     * Checks that a body that isn't JSON throws instead of returning an empty array.
     *
     * @return void
     */
    public function testGetThrowsOnInvalidJson(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '<html>')]);

        $this->expectException(BlizzardException::class);

        $adapter->get('/test', [], 'token');
    }

    /**
     * Checks that a successful post sends form params and decodes the JSON body.
     *
     * @return void
     */
    public function testPostSuccess(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{"access_token":"abc"}')]);

        $this->assertSame(['access_token' => 'abc'], $adapter->post('/oauth/token', ['grant_type' => 'x'], null));

        $request = $this->sentRequest(0);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('grant_type=x', (string) $request->getBody());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    /**
     * Checks that a post with a token sends it in the Authorization header.
     *
     * @return void
     */
    public function testPostSendsHeaders(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{}')]);

        $adapter->post('/test', ['a' => 'b'], 'token');

        $request = $this->sentRequest(0);
        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    /**
     * Checks that rejected credentials on a post throw an AuthenticationException.
     *
     * @return void
     */
    public function testPostThrowsOnUnauthorized(): void
    {
        $adapter = $this->adapterReturning([new Response(401)]);

        $this->expectException(AuthenticationException::class);

        $adapter->post('/oauth/token', [], null);
    }

    /**
     * Checks that a JSON body that isn't an object or array throws, since every endpoint returns an object.
     *
     * @return void
     */
    public function testGetThrowsOnScalarJson(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '"maintenance"')]);

        $this->expectException(BlizzardException::class);

        $adapter->get('/test', [], 'token');
    }
}
