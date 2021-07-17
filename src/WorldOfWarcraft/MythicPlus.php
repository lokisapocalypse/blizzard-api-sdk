<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class MythicPlus extends WorldOfWarcraft
{
    /**
     * Get all the pets that a character has
     *
     * @param string $character
     * @param string $realm
     * @param string $region
     * @return array
     */
    public function getProfileForCharacter($character, $realm, $region = 'us') : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'profile-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/profile/wow/character/$realm/$character/mythic-keystone-profile", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/profile/wow/character/$realm/$character/mythic-keystone-profile", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}
