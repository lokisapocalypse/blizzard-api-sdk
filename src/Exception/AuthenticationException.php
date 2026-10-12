<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Exception;

/**
 * Thrown when the api or OAuth server responds with 401, e.g. for an expired token or bad credentials.
 */
class AuthenticationException extends BlizzardException
{
}
