<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class CharacterMedia extends WorldOfWarcraft
{
    /**
     * Get all profile images for the provided character
     *
     * @param string $character
     * @param string $realm
     * @param string $region
     * @return array
     */
    public function getMedia(
        string $character,
        string $realm,
        string $region
    ) : array {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'profile-' . $region,
        ];

        // temporarily switch regions if needed
        $baseUrl = $this->apiAdapter->getBaseUrl();
        $this->apiAdapter->changeBaseUrl(str_replace('us.', "$region.", $baseUrl));

        $url = "/profile/wow/character/$realm/$character/character-media";

        $response = $this->apiAdapter->get($url, $params);

        $this->apiAdapter->changeBaseUrl($baseUrl);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            $response = $this->apiAdapter->get($url, $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}
