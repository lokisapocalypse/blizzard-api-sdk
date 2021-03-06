<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Item extends WorldOfWarcraft
{
    /**
     * Get all the pets that a character has
     *
     * @param string $character
     * @param string $realm
     * @param string $region
     * @return array
     */
    public function getById($id) : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'static-us',
            'region' => 'us',
        ];

        $response = $this->apiAdapter->get("/data/wow/item/$id", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/item/$id", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}
