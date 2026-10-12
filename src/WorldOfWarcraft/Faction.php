<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Faction endpoints.
 */
class Faction extends WorldOfWarcraft
{
    /**
     * Get all the factions.
     *
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the faction index, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function allFactions(string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/data/wow/reputation-faction/index", $params, $this->accessToken);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-faction/index", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }

    /**
     * Get the detailed information for a specific faction.
     *
     * @param int $id the faction id
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the faction, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getFaction(int $id, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'static-' . $region,
            'region' => $region,
        ];

        $response = $this->apiAdapter->get("/data/wow/reputation-faction/$id", $params, $this->accessToken);

        // if the token has expired, refresh and try again but only once
        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            // we also need to update the access token as part of the response
            $response = $this->apiAdapter->get("/data/wow/reputation-faction/$id", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }
}
