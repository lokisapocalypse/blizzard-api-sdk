<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Exception\RateLimitException;
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
     * @param RetryPolicy|null $retryPolicy when to send a failed request again, or null to never retry
     * @return SleeplessGuzzleAdapter
     */
    protected function adapterReturning(
        array $responses,
        string $url = 'https://us.api.blizzard.com',
        ?RetryPolicy $retryPolicy = null
    ): SleeplessGuzzleAdapter {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            $this->requests[] = $request;

            return $request;
        }));

        return new SleeplessGuzzleAdapter($url, new Client(['handler' => $stack]), $retryPolicy);
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
            'too many requests' => [429, RateLimitException::class],
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
     * Sends a get that fails with the given response and returns the exception it threw.
     *
     * @param ResponseInterface $response the error response the api sends
     * @return BlizzardException
     */
    protected function exceptionFromGet(ResponseInterface $response): BlizzardException
    {
        $adapter = $this->adapterReturning([$response]);

        try {
            $adapter->get('/test', [], 'token');
        } catch (BlizzardException $e) {
            return $e;
        }

        $this->fail('Expected an exception.');
    }

    /**
     * Checks that the api's error detail is added to the message and its decoded body is kept on the exception.
     *
     * @return void
     */
    public function testErrorIncludesApiDetail(): void
    {
        $body = ['code' => 403, 'type' => 'BLZWEBAPI00000403', 'detail' => 'Account is not linked'];
        $e = $this->exceptionFromGet(new Response(403, [], (string) json_encode($body)));

        $this->assertSame('GET /test returned 403 Forbidden: Account is not linked', $e->getMessage());
        $this->assertSame($body, $e->getResponseBody());
    }

    /**
     * Checks that a detail that only repeats the reason phrase isn't added twice, but the body is still kept.
     *
     * @return void
     */
    public function testErrorSkipsDetailMatchingReasonPhrase(): void
    {
        $body = ['code' => 404, 'type' => 'BLZWEBAPI00000404', 'detail' => 'Not Found'];
        $e = $this->exceptionFromGet(new Response(404, [], (string) json_encode($body)));

        $this->assertInstanceOf(NotFoundException::class, $e);
        $this->assertSame('GET /test returned 404 Not Found', $e->getMessage());
        $this->assertSame($body, $e->getResponseBody());
    }

    /**
     * Checks that the OAuth server's error_description is added to the message.
     *
     * @return void
     */
    public function testErrorIncludesOAuthErrorDescription(): void
    {
        $adapter = $this->adapterReturning([
            new Response(401, [], '{"error":"invalid_client","error_description":"Bad client credentials"}'),
        ]);

        try {
            $adapter->post('/oauth/token', [], null);
            $this->fail('Expected an exception.');
        } catch (AuthenticationException $e) {
            $this->assertSame('POST /oauth/token returned 401 Unauthorized: Bad client credentials', $e->getMessage());
            $this->assertSame(
                ['error' => 'invalid_client', 'error_description' => 'Bad client credentials'],
                $e->getResponseBody()
            );
        }
    }

    /**
     * Lists error bodies that have no usable detail.
     *
     * @return array<string, array{string, array<mixed>|null}>
     */
    public static function errorBodyWithoutDetailProvider(): array
    {
        return [
            'empty' => ['', null],
            'html' => ['<html><body>Bad Gateway</body></html>', null],
            'scalar json' => ['"oops"', null],
            'json without detail' => ['{"code":502}', ['code' => 502]],
            'non-string detail' => ['{"detail":{"reason":"x"}}', ['detail' => ['reason' => 'x']]],
            'blank detail' => ['{"detail":"  "}', ['detail' => '  ']],
        ];
    }

    /**
     * Checks that an error body with no usable detail still gives the usual message, with the body kept when it's JSON.
     *
     * @param string $rawBody the error response body
     * @param array<mixed>|null $expectedBody the decoded body the exception should expose
     * @return void
     */
    #[DataProvider('errorBodyWithoutDetailProvider')]
    public function testErrorWithoutDetail(string $rawBody, ?array $expectedBody): void
    {
        $e = $this->exceptionFromGet(new Response(502, [], $rawBody));

        $this->assertSame(BlizzardException::class, $e::class);
        $this->assertSame(502, $e->getCode());
        $this->assertSame('GET /test returned 502 Bad Gateway', $e->getMessage());
        $this->assertSame($expectedBody, $e->getResponseBody());
    }

    /**
     * Checks that a 429 throws a RateLimitException with the Retry-After seconds.
     *
     * @return void
     */
    public function testRateLimitExposesRetryAfter(): void
    {
        $e = $this->exceptionFromGet(new Response(429, ['Retry-After' => '7']));

        $this->assertInstanceOf(RateLimitException::class, $e);
        $this->assertSame(429, $e->getCode());
        $this->assertSame('GET /test returned 429 Too Many Requests', $e->getMessage());
        $this->assertSame(7, $e->getRetryAfter());
    }

    /**
     * Checks that a Retry-After sent as an HTTP date is turned into seconds from now.
     *
     * @return void
     */
    public function testRateLimitReadsRetryAfterDate(): void
    {
        $date = gmdate('D, d M Y H:i:s', time() + 30) . ' GMT';
        $e = $this->exceptionFromGet(new Response(429, ['Retry-After' => $date]));

        $this->assertInstanceOf(RateLimitException::class, $e);
        $this->assertNotNull($e->getRetryAfter());
        $this->assertEqualsWithDelta(30, $e->getRetryAfter(), 2);
    }

    /**
     * Lists Retry-After headers that give no usable delay.
     *
     * @return array<string, array{array<string, string>}>
     */
    public static function missingRetryAfterProvider(): array
    {
        return [
            'no header' => [[]],
            'blank' => [['Retry-After' => ' ']],
            'unreadable' => [['Retry-After' => 'soon']],
        ];
    }

    /**
     * Checks that a 429 without a usable Retry-After still throws a RateLimitException, with no delay.
     *
     * @param array<string, string> $headers the response headers
     * @return void
     */
    #[DataProvider('missingRetryAfterProvider')]
    public function testRateLimitWithoutRetryAfter(array $headers): void
    {
        $e = $this->exceptionFromGet(new Response(429, $headers));

        $this->assertInstanceOf(RateLimitException::class, $e);
        $this->assertNull($e->getRetryAfter());
    }

    /**
     * Checks that a temporary failure isn't retried unless retries are turned on.
     *
     * @return void
     */
    public function testDoesNotRetryByDefault(): void
    {
        $adapter = $this->adapterReturning([new Response(503), new Response(200, [], '{}')]);

        try {
            $adapter->get('/test', [], 'token');
            $this->fail('Expected an exception.');
        } catch (BlizzardException $e) {
            $this->assertSame(503, $e->getCode());
        }

        $this->assertCount(1, $this->requests);
        $this->assertSame([], $adapter->waits);
    }

    /**
     * Checks that a 429 and a 5xx are retried with a growing wait until the request succeeds.
     *
     * @return void
     */
    public function testRetriesUntilSuccess(): void
    {
        $adapter = $this->adapterReturning(
            [new Response(503), new Response(429), new Response(200, [], '{"name":"Ashbringer"}')],
            retryPolicy: new RetryPolicy(3)
        );

        $this->assertSame(['name' => 'Ashbringer'], $adapter->get('/test', [], 'token'));
        $this->assertCount(3, $this->requests);
        $this->assertSame([0.5, 1.0], $adapter->waits);
    }

    /**
     * Checks that a retry waits as long as the server's Retry-After says.
     *
     * @return void
     */
    public function testRetryWaitsForRetryAfter(): void
    {
        $adapter = $this->adapterReturning(
            [new Response(429, ['Retry-After' => '2']), new Response(200, [], '{}')],
            retryPolicy: new RetryPolicy(1)
        );

        $adapter->get('/test', [], 'token');

        $this->assertSame([2.0], $adapter->waits);
    }

    /**
     * Checks that the last failure is thrown once the request has been retried the configured number of times.
     *
     * @return void
     */
    public function testGivesUpAfterMaxRetries(): void
    {
        $adapter = $this->adapterReturning(
            [new Response(500), new Response(502), new Response(429, ['Retry-After' => '1'])],
            retryPolicy: new RetryPolicy(2)
        );

        try {
            $adapter->get('/test', [], 'token');
            $this->fail('Expected an exception.');
        } catch (RateLimitException $e) {
            $this->assertSame(1, $e->getRetryAfter());
        }

        $this->assertCount(3, $this->requests);
        $this->assertSame([0.5, 1.0], $adapter->waits);
    }

    /**
     * Checks that a Retry-After longer than the policy's maximum delay throws straight away instead of blocking.
     *
     * @return void
     */
    public function testGivesUpWhenRetryAfterIsTooLong(): void
    {
        $adapter = $this->adapterReturning(
            [new Response(429, ['Retry-After' => '3600']), new Response(200, [], '{}')],
            retryPolicy: new RetryPolicy(3)
        );

        try {
            $adapter->get('/test', [], 'token');
            $this->fail('Expected an exception.');
        } catch (RateLimitException $e) {
            $this->assertSame(3600, $e->getRetryAfter());
        }

        $this->assertCount(1, $this->requests);
        $this->assertSame([], $adapter->waits);
    }

    /**
     * Checks that an error that won't go away on its own isn't retried.
     *
     * @return void
     */
    public function testDoesNotRetryPermanentErrors(): void
    {
        $adapter = $this->adapterReturning(
            [new Response(404), new Response(200, [], '{}')],
            retryPolicy: new RetryPolicy(3)
        );

        $this->expectException(NotFoundException::class);

        try {
            $adapter->get('/test', [], 'token');
        } finally {
            $this->assertCount(1, $this->requests);
        }
    }

    /**
     * Checks that a post is retried too, sending the same form params again.
     *
     * @return void
     */
    public function testPostRetries(): void
    {
        $adapter = $this->adapterReturning(
            [new Response(503), new Response(200, [], '{"access_token":"abc"}')],
            retryPolicy: new RetryPolicy(1)
        );

        $this->assertSame(['access_token' => 'abc'], $adapter->post('/token', ['grant_type' => 'x'], null));
        $this->assertSame('grant_type=x', (string) $this->sentRequest(1)->getBody());
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
