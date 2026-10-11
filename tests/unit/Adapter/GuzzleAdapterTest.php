<?php

namespace Fusani\Blizzard\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

#[CoversClass(GuzzleAdapter::class)]
class GuzzleAdapterTest extends TestCase
{
    /** @var list<array{request: RequestInterface}> */
    protected $history = [];

    /**
     * Builds an adapter whose Guzzle client returns the given responses instead of calling the network.
     *
     * @param list<Response> $responses the responses to return, in order
     * @param string $url the base url for the adapter
     * @return GuzzleAdapter
     */
    protected function adapterReturning(array $responses, string $url = 'https://us.api.blizzard.com'): GuzzleAdapter
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $adapter = new GuzzleAdapter($url);
        (fn () => $this->client = new Client(['handler' => $stack]))->call($adapter);

        return $adapter;
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
     * Checks that the base url can be replaced.
     *
     * @return void
     */
    public function testChangeBaseUrl(): void
    {
        $adapter = new GuzzleAdapter('https://us.api.blizzard.com');

        $this->assertSame($adapter, $adapter->changeBaseUrl('https://eu.api.blizzard.com'));
        $this->assertSame('https://eu.api.blizzard.com', $adapter->getBaseUrl());
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

        $request = $this->history[0]['request'];
        $this->assertSame('https://us.api.blizzard.com/test?locale=en_US', (string) $request->getUri());
        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
    }

    /**
     * Checks that a region parameter switches the request to that region's host.
     *
     * @return void
     */
    public function testGetUsesRegion(): void
    {
        $adapter = $this->adapterReturning([new Response(200, [], '{}')]);

        $adapter->get('/test', ['region' => 'eu'], 'token');

        $this->assertSame('eu.api.blizzard.com', $this->history[0]['request']->getUri()->getHost());
    }

    /**
     * Checks that an error response from a get is returned as a failure array.
     *
     * @return void
     */
    public function testGetFailure(): void
    {
        $adapter = $this->adapterReturning([new Response(401)]);

        $this->assertSame(
            ['success' => false, 'statusCode' => 401, 'message' => 'Unauthorized'],
            $adapter->get('/test', [], 'token')
        );
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
        $this->assertSame('grant_type=x', (string) $this->history[0]['request']->getBody());
    }

    /**
     * Checks that an error response from a post returns false.
     *
     * @return void
     */
    public function testPostFailure(): void
    {
        $adapter = $this->adapterReturning([new Response(400)]);

        $this->assertFalse($adapter->post('/oauth/token', [], null));
    }
}
