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
            'namespace' => 'profile-' . $region,
        ];

        // temporarily switch regions if needed
        $baseUrl = $this->apiAdapter->getBaseUrl();
        $this->apiAdapter->changeBaseUrl(str_replace('us.', "$region.", $baseUrl));

        $url = "/profile/wow/character/$realm/$character/character-media";

        $response = $this->apiAdapter->get($url, $params, $this->accessToken);

        $this->apiAdapter->changeBaseUrl($baseUrl);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $response = $this->apiAdapter->get($url, $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }
}
