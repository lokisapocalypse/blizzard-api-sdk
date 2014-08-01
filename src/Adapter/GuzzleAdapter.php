<?php
namespace Fusani\Auth\Adapter;

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception;

class GuzzleAdapter implements \Fusani\Auth\Adapter\Adapter
{
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
            $parts['path']
        );

        // build the guzzle client
        $this->client = new Client(
            array(
                'base_url' => $baseUrl,
                'defaults' => array(
                    'auth' => array($parts['user'], $parts['pass']),
                    'verify' => false,
                )
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $query)
    {
        $url = $path . '?' . http_build_query($query);

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
                $path, array(
                    'body' => $query,
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
    public function put($path, $query)
    {   
        try {
            $response = $this->client->put(
                $path,
                array(
                    'headers' => array('Content-Type' => 'application/json'),
                    'body' => json_encode($query)
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
