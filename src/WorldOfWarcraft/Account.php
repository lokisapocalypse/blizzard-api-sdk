<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

/**
 * Account profile endpoints, which need a user's authorization code.
 */
class Account extends WorldOfWarcraft
{
    /**
     * Get all characters for this account, exchanging the user's authorization code for a token first.
     *
     * @param string $token the authorization code from the OAuth redirect
     * @param string $redirectUri the redirect uri used to get the authorization code
     * @param string $region the region the account is in, e.g. `us` or `eu`
     * @return array<mixed> the account profile, or an array with `success`, `statusCode` and `message` on failure
     */
    public function getCharacters(string $token, string $redirectUri, string $region = 'us'): array
    {
        $tokenParams = [
            'scope' => 'wow.profile',
            'code' => $token,
            'redirect_uri' => $redirectUri,
        ];

        $accessToken = $this->tokenFrom($this->getAccessToken('authorization_code', $tokenParams));

        $params = [
            'namespace' => 'profile-' . $region,
        ];

        return $this->apiAdapter->get('/profile/user/wow', $params, $accessToken);
    }
}
