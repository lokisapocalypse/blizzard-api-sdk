<?php

declare(strict_types=1);

namespace Fusani\Blizzard\WorldOfWarcraft;

use Fusani\Blizzard\Exception\AuthenticationException;
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

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
     * @return array<mixed> the account profile
     * @throws AuthenticationException if the authorization code or token is rejected
     * @throws NotFoundException if the api responds with 404
     * @throws BlizzardException if the request fails for any other reason
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

        return $this->apiAdapter->get('/profile/user/wow', $params, $accessToken, $region);
    }
}
