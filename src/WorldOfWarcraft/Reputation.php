<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Reputation endpoints.
 */
class Reputation extends WorldOfWarcraft
{
    /**
     * Get all reputation tiers.
     *
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the reputation tiers index, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function allReputationTiers(string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/data/wow/reputation-tiers/index", $params, $this->accessToken);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-tiers/index", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }

    /**
     * Get the reputation for a given character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the character's reputations, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getForCharacter(string $character, string $realm, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'profile-' . $region,
            'region' => $region,
        ];

        $url = "/profile/wow/character/$realm/$character/reputations";

        $response = $this->apiAdapter->get($url, $params, $this->accessToken);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get($url, $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }

    /**
     * Get a reputation tier.
     *
     * @param int $id the reputation tier id
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the reputation tier, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getReputationTier(int $id, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/data/wow/reputation-tiers/$id", $params, $this->accessToken);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-tiers/$id", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }
}
