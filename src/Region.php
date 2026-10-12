<?php

declare(strict_types=1);

namespace Fusani\Blizzard;

/**
 * A Battle.net region, which decides the api and OAuth hosts and the namespaces a request uses.
 */
enum Region: string
{
    case Us = 'us';
    case Eu = 'eu';
    case Kr = 'kr';
    case Tw = 'tw';
    case Cn = 'cn';

    /**
     * Get the base url of the game data and profile apis.
     *
     * @return string
     */
    public function apiHost(): string
    {
        return match ($this) {
            self::Cn => 'https://gateway.battlenet.com.cn',
            default => "https://{$this->value}.api.blizzard.com",
        };
    }

    /**
     * Get the base url of the OAuth server that issues tokens for this region.
     *
     * @return string
     */
    public function oauthHost(): string
    {
        return match ($this) {
            self::Cn => 'https://oauth.battlenet.com.cn',
            default => 'https://oauth.battle.net',
        };
    }

    /**
     * Get the namespace for character and account profiles.
     *
     * @return string
     */
    public function profileNamespace(): string
    {
        return "profile-{$this->value}";
    }

    /**
     * Get the namespace for game data that changes only with patches, e.g. factions, tiers and items.
     *
     * @return string
     */
    public function staticNamespace(): string
    {
        return "static-{$this->value}";
    }

    /**
     * Get the namespace for game data that changes between patches, e.g. realms.
     *
     * @return string
     */
    public function dynamicNamespace(): string
    {
        return "dynamic-{$this->value}";
    }
}
