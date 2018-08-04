<?php

namespace Fusani\Blizzard\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception;

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
        $this->client = new Client([
            'base_url' => $baseUrl,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $params)
    {
        $url = $path . '?' . http_build_query($params);

        try {
            $response = $this->client->get($url);
            $results = $response->json();
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
    public function post($path, $params)
    {
        try {
            $response = $this->client->post(
                $path,
                array(
                    'body' => $params,
                )
            );

            $results = $response->json();
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
            $response = $this->client->put(
                $path,
                array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body' => json_encode($params)
                )
            );
            $results = $response->json();
        } catch (Exception\BadResponseException $e) {
            // Guzzle throws exceptions on non-200 series HTTP codes.
            // BadResponseException means authentication failed,
            // other exceptions probably means that the request is bad
            return false;
        }

        return $results;
    }
}
