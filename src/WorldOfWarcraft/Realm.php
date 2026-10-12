<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Realm endpoints.
 */
class Realm extends WorldOfWarcraft
{
    /**
     * Get all the realms for the specified region.
     *
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the realm index, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getAllRealmsByRegion(string $region): array
    {
        $params = [
            'namespace' => 'dynamic-'.strtolower($region),
        ];

        // temporarily switch regions if needed
        $baseUrl = $this->apiAdapter->getBaseUrl();
        $this->apiAdapter->changeBaseUrl(str_replace('us.', "$region.", $baseUrl));

        $response = $this->apiAdapter->get("/data/wow/realm/index", $params, $this->accessToken);

        $this->apiAdapter->changeBaseUrl($baseUrl);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            if ($this->accessToken !== null) {
                $params['access_token'] = $this->accessToken;
            }

            $response = $this->apiAdapter->get("/data/wow/realm/index", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }
}
