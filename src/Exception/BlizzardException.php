<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Exception;

use RuntimeException;

/**
 * Thrown when a request to the Blizzard api or OAuth server fails.
 *
 * The exception code is the HTTP status code when the server responded, or 0 when it didn't.
 */
class BlizzardException extends RuntimeException
{
}
