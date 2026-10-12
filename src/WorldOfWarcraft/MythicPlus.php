<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Mythic+ keystone profile endpoints.
 */
class MythicPlus extends WorldOfWarcraft
{
    /**
     * Get the Mythic+ keystone profile for a character.
     *
     * @param string $character the character's name, lowercased
     * @param string $realm the realm's slug
     * @param string $region the region, e.g. `us` or `eu`
     * @return array<mixed> the keystone profile, with `access_token` added if the token was refreshed,
     *                      or an array with `success`, `statusCode` and `message` on failure
     */
    public function getProfileForCharacter(string $character, string $realm, string $region = 'us'): array
    {
        $params = [
            'namespace' => 'profile-' . $region,
            'region' => $region,
        ];

        $url = "/profile/wow/character/$realm/$character/mythic-keystone-profile";

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
}
