<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Item extends WorldOfWarcraft
{
    /**
     * Get the item by the associated id
     *
     * @param int $id
     * @return array
     */
    public function getById(int $id) : array
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

    /**
     * Get the icon of the associated item id
     */
    public function getIcon(int $id) : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'static-us',
            'region' => 'us',
        ];

        $response = $this->apiAdapter->get("/data/wow/media/item/$id", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/media/item/$id", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}
