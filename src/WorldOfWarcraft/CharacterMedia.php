<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Character media (avatar and render images) endpoints.
 */
class CharacterMedia extends WorldOfWarcraft
{
    /**
     * Get all profile images for the provided character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param string $region the region the realm is in, e.g. `us` or `eu`
     * @return array<mixed> the character media, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getMedia(
        string $character,
        string $realm,
        string $region
    ): array {
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
