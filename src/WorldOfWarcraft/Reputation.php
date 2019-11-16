<?php

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Adapter;
use Guzzle\Http\Exception;

class Reputation extends WorldOfWarcraft
{
    /**
     * Get all reputation tiers
     *
     * @param string $region
     * @return array
     */
    public function allReputationTiers(string $region = 'us') : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/data/wow/reputation-tiers/index", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-tiers/index", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }

    /**
     * Get the reputation for a given character
     *
     * @param string $character
     * @param string $realm
     * @param string $region
     * @return array
     */
    public function getForCharacter($character, $realm, $region = 'us') : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'profile-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/profile/wow/character/$realm/$character/reputations", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/profile/wow/character/$realm/$character/reputations", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }

    /**
     * Get the reputation tier
     *
     * @param int $id
     * @param string $region
     * @return array
     */
    public function getReputationTier($id, $region = 'us') : array
    {
        $params = [
            'access_token' => $this->accessToken,
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/data/wow/reputation-tiers/$id", $params);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $params['access_token'] = $this->accessToken;

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-tiers/$id", $params);
            $response['access_token'] = $params['access_token'];
        }

        return $response;
    }
}
