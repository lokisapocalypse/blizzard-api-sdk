<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Adapter;

/**
 * Testing adapter that returns queued responses instead of making requests.
 */
class AdapterStub implements Adapter
{
    /** @var string */
    protected string $baseUrl;

    /** @var list<array<mixed>> */
    protected array $responses;

    /**
     * {@inheritDoc}
     */
    public function __construct(string $url)
    {
        $this->baseUrl = $url;
        $this->responses = [];
    }

    /**
     * Queue a response to be returned by the next request.
     *
     * @param array<mixed> $response the response to return
     * @return void
     */
    public function addResponse(array $response): void
    {
        $this->responses[] = $response;
    }

    /**
     * Return the next queued response for a generic service call.
     *
     * @param string $service the service being called
     * @param string $method the method being called
     * @param array<string, mixed> $params the parameters for the call
     * @return array<mixed> the next queued response, or an empty array if there are none
     */
    public function call(string $service, string $method, array $params): array
    {
        return $this->getNextResponse();
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path, array $params, ?string $accessToken, ?string $region = null): array
    {
        return $this->getNextResponse();
    }

    /**
     * Take the next queued response off the queue.
     *
     * @return array<mixed> the next queued response, or an empty array if there are none
     */
    protected function getNextResponse(): array
    {
        return array_shift($this->responses) ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function post(string $path, array $params, ?string $accessToken): array
    {
        return $this->getNextResponse();
    }
}
