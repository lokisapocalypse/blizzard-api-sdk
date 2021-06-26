<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Mount extends WorldOfWarcraft
{
    /**
     * Get mounts collected for the given character
     *
     * @param string $character
     * @param string $realm
     * @param string $region
     * @return array
     */
    public function getMountsForCharacter(
        string $character,
        string $realm,
        string $region
    ) : array {
        $params = [
            'access_token' => $this->accessToken,
            'fields' => 'mounts',
        ];

        // temporarily switch regions if needed
        $baseUrl = $this->apiAdapter->getBaseUrl();
        $this->apiAdapter->changeBaseUrl(str_replace('us.', "$region.", $baseUrl));

        $response = $this->apiAdapter->get("/wow/character/$realm/$character", $params);

        $this->apiAdapter->changeBaseUrl($baseUrl);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            $response = $this->apiAdapter->get("/wow/character/$realm/$character", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}