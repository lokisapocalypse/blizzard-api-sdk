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
     * @param string $region
     * @return array
     */
    public function getCharacters(string $token, string $region = 'us') : array
    {
        $params = [
            'access_token' => $token
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        return $this->apiAdapter->get("/wow/user/characters", $params);
    }
}
