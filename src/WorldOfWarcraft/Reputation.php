<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Reputation extends WorldOfWarcraft
{
    /**
     * Get all the factions
     *
     * @param string $region
     * @return array
     */
    public function allFactions(string $region = 'us') : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $classes = [];

        $response = $this->apiAdapter->get("/data/wow/reputation-faction/index", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-faction/index", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}
