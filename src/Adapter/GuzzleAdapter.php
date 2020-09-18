<?php

namespace Fusani\Blizzard\Adapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception;

class GuzzleAdapter implements Adapter
{
    /** @var string */
    protected $baseUrl;

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

        $this->baseUrl = $baseUrl;

        // build the guzzle client
        $this->client = new Client();
    }

    /**
     * @return string
     */
    public function getBaseUrl() : string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $url
     * @return Adapter
     */
    public function changeBaseUrl(string $url)
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, $params)
    {
        $url = $path . '?' . http_build_query($params);

        // if a region is set, override the base url
        if (!empty($params['region'])) {
            $this->baseUrl = str_replace('us.', $params['region'] . '.', $this->baseUrl);
        }

        try {
            $response = $this->client->get($this->baseUrl.$url);
            return json_decode($response->getBody()->getContents(), true);
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
            $response = $this->client->post($this->baseUrl.$path, ['form_params' => $params]);
            $results = json_decode($response->getBody()->getContents(), true);
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
