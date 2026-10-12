<?php

declare(strict_types=1);

namespace Fusani\Blizzard\Exception;

/**
 * Thrown when the api responds with 404, e.g. for an unknown character, realm or item.
 */
class NotFoundException extends BlizzardException
{
}
