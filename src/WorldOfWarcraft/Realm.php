<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Realm extends WorldOfWarcraft
{
    /**
     * Get all the realms for the specified region
     *
     * @param string $region
     * @return array
     */
    public function getAllRealmsByRegion(string $region) : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'dynamic-'.strtolower($region),
        ];

        // temporarily switch regions if needed
        $baseUrl = $this->apiAdapter->getBaseUrl();
        $this->apiAdapter->changeBaseUrl(str_replace('us.', "$region.", $baseUrl));

        $response = $this->apiAdapter->get("/data/wow/realm/index", $params);

        $this->apiAdapter->changeBaseUrl($baseUrl);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            $response = $this->apiAdapter->get("/data/wow/realm/index", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}