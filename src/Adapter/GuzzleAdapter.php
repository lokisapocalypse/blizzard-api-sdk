<?php

namespace Fusani\Blizzard\Adapter;

use Guzzle\Http\Client;
use Guzzle\Http\Exception;

class GuzzleAdapter implements Adapter
{
    /** @var Client */
    protected $client;

    /**
     * {@inheritDoc}
     */
    public function __construct($url)
    {
        // break the url down
        $parts = parse_url($url);

        // build the base url
        $baseUrl = sprintf(
            '%s://%s%s',
            $parts['scheme'],
            $parts['host'],
            isset($parts['path']) ? $parts['path'] : ''
        );

        // build the guzzle client
        $this->client = new Client($baseUrl);
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $params)
    {
        $url = $path . '?' . http_build_query($params);

        try {
            $request = $this->client->get($url);
            $response = $this->client->send($request);

            $results = json_decode($response->getBody(), true);
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            $response = $e->getResponse();

            return [
                'success' => false,
                'statusCode' => $response->getStatusCode(),
                'message' => $response->getReasonPhrase(),
            ];
        } catch (Exception\ParseException $e) {
            return [];
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $params)
    {
        try {
            $request = $this->client->post($path, ['body' => $params]);
            $response = $this->client->send($request);

            $results = json_decode($response->getBody(), true);
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            return false;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $params)
    {
        try {
            $request = $this->client->put(
                $path,
                [
                    'headers' => array('Content-Type' => 'application/json'),
                    'body' => json_encode($params)
                ]
            );
            $response = $this->client->send($request);

            $results = json_decode($response->getBody(), true);
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            return false;
        }

        return $results;
    }
}
