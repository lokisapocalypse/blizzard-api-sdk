<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Mount collection endpoints.
 */
class Mount extends WorldOfWarcraft
{
    /**
     * Get mounts collected for the given character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the character with its mounts, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getMountsForCharacter(
        string $character,
        string $realm,
        string $region
    ): array {
        $params = [
            'fields' => 'mounts',
        ];

        // temporarily switch regions if needed
        $baseUrl = $this->apiAdapter->getBaseUrl();
        $this->apiAdapter->changeBaseUrl(str_replace('us.', "$region.", $baseUrl));

        $response = $this->apiAdapter->get("/wow/character/$realm/$character", $params, $this->accessToken);

        $this->apiAdapter->changeBaseUrl($baseUrl);

        if (!empty($response['statusCode']) && $response['statusCode'] == 401) {
            $this->refreshToken();

            $response = $this->apiAdapter->get("/wow/character/$realm/$character", $params, $this->accessToken);
            $response['access_token'] = $this->accessToken;
        }

        return $response;
    }
}
