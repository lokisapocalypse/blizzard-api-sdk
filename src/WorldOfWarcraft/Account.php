<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Account extends WorldOfWarcraft
{
    /**
     * Get all characters for this account
     *
     * @param string $token
     * @param string $redirectUri
     * @param string $region
     * @return array
     */
    public function getCharacters(string $token, string $redirectUri, string $region = 'us') : array
    {
        $tokenParams = [
            'scope' => 'wow.profile',
            'code' => $token,
            'redirect_uri' => $redirectUri,
        ];

        $accessToken = $this->getAccessToken('authorization_code', $tokenParams);

        $params = [
            'access_token' => $accessToken['access_token'],
            'region' => $region,
        ];

        return $this->apiAdapter->get("/wow/user/characters", $params);
    }
}
