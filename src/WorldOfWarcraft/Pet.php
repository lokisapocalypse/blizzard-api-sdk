<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Pet extends WorldOfWarcraft
{
    /**
     * Get all the pets that a character has
     *
     * @param string $character
     * @param string $realm
     * @param string $region
     * @return array
     */
    public function getForCharacter($character, $realm, $region = 'us') : array
    {
        $params = [
            'namespace' => 'profile-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/profile/wow/character/$realm/$character/collections/pets", $params, $this->accessToken);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/profile/wow/character/$realm/$character/collections/pets", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }
}
