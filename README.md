# Blizzard API SDK

[![CI](https://github.com/lokisapocalypse/blizzard-api-sdk/actions/workflows/ci.yml/badge.svg?branch=development)](https://github.com/lokisapocalypse/blizzard-api-sdk/actions/workflows/ci.yml)
[![Packagist version](https://img.shields.io/packagist/v/fusani/blizzard)](https://packagist.org/packages/fusani/blizzard)
[![PHP version](https://img.shields.io/packagist/dependency-v/fusani/blizzard/php)](composer.json)
[![License: MIT](https://img.shields.io/packagist/l/fusani/blizzard)](LICENSE)

A PHP client for the [Blizzard Battle.net API](https://community.developer.battle.net/documentation), covering
World of Warcraft characters, items, mounts, pets, reputations, realms and Mythic+.

It gets and renews OAuth tokens for you, works in every region (including China), and throws typed exceptions
when a request fails. Responses come back as the API's JSON, decoded into arrays.

## Requirements

- PHP 8.5 or newer, with the `mbstring` extension
- A client ID and secret from the [Blizzard developer portal](https://community.developer.battle.net/access/clients)

## Installation

```bash
composer require fusani/blizzard
```

## Usage

Create a `ServiceFactory` with your client credentials, and ask it for the service you need:

```php
use Fusani\Blizzard\Region;
use Fusani\Blizzard\ServiceFactory;

$factory = new ServiceFactory($clientId, $clientSecret);

$realms = $factory->createRealmService()->getAllRealmsByRegion(Region::Eu);
```

The services share one HTTP client and one token, so build the factory once and reuse it.

### Options

```php
$factory = new ServiceFactory($clientId, $clientSecret, $cache, 'en_US');
```

- **`$cache`** (optional): a [PSR-16](https://www.php-fig.org/psr/psr-16/) cache, such as Laravel's or Symfony's.
  Without one, a token lasts only as long as the PHP process. With one, it's shared between requests until it
  expires.
- **`$locale`** (optional): the locale names come back in, such as `en_US` or `de_DE`. Without one, each name is
  an object holding every translation.
- **`$timeout`** and **`$connectTimeout`** (optional): how many seconds a request may take in total, and how long to
  wait for a connection, before it fails. They default to 10 and 5 seconds, apply to both API and OAuth requests, and
  must be more than 0. A request that times out throws a `BlizzardException` with code 0.

```php
$factory = new ServiceFactory($clientId, $clientSecret, timeout: 30, connectTimeout: 3);
```

- **`$maxRetries`** (optional): how many times to send a request again after a 429 (too many requests) or a temporary
  server error (500, 502, 503 or 504). It defaults to 0, which never retries. The SDK waits 0.5 seconds before the
  first retry and doubles the wait each time, up to 10 seconds. If the server sends `Retry-After`, the SDK waits that
  long instead, or gives up straight away if it's more than 10 seconds. Other errors, timeouts and connection
  failures are never retried.

```php
$factory = new ServiceFactory($clientId, $clientSecret, maxRetries: 3);
```

- **`$httpClient`** (optional): your own [Guzzle](https://docs.guzzlephp.org/) client, which both API and OAuth
  requests are sent with. Use it to add middleware for logging or retries, a proxy, or other request options. The
  `$timeout` and `$connectTimeout` arguments are ignored when you pass one, and Guzzle's own default is to wait
  forever, so set timeouts on the client yourself.

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;

$stack = HandlerStack::create();
$stack->push(Middleware::log($logger, new MessageFormatter('{method} {uri} {code}')));

$factory = new ServiceFactory($clientId, $clientSecret, httpClient: new Client([
    'handler' => $stack,
    'timeout' => 10,
    'connect_timeout' => 5,
]));
```

The log lines include the URL, but not the client secret, which is sent in the body of OAuth requests. A formatter
that logs request bodies (such as `{req_body}` or `MessageFormatter::DEBUG`) would write the secret to your logs.

### Regions

Every method takes a `Region`: `Region::Us`, `Region::Eu`, `Region::Kr`, `Region::Tw` or `Region::Cn`. Most default to
`Region::Us`. To convert a stored string, use `Region::from(strtolower($region))`.

### Services

Character names and realm slugs can be passed in any case.

**Character media**: avatar, inset and render images.

```php
$media = $factory->createCharacterMediaService()->getMedia('thrall', 'area-52', Region::Us);
```

**Factions**

```php
$factions = $factory->createFactionService();
$all = $factions->allFactions();
$horde = $factions->getFaction(67);
```

**Items**

```php
$items = $factory->createItemService();
$thunderfury = $items->getById(19019);
$icon = $items->getIcon(19019);
```

**Mounts**: a character's mount collection.

```php
$mounts = $factory->createMountService()->getMountsForCharacter('thrall', 'area-52', Region::Us);
```

**Mythic+**: a character's Mythic+ profile and rating.

```php
$profile = $factory->createMythicPlusService()->getProfileForCharacter('thrall', 'area-52');
```

**Pets**: a character's battle pet collection.

```php
$pets = $factory->createPetService()->getForCharacter('thrall', 'area-52');
```

**Realms**

```php
$realms = $factory->createRealmService()->getAllRealmsByRegion(Region::Us);
```

**Reputation**: a character's standings, and the tiers that standings are measured in.

```php
$reputation = $factory->createReputationService();
$standings = $reputation->getForCharacter('thrall', 'area-52');
$tiers = $reputation->allReputationTiers();
$tier = $reputation->getReputationTier(2);
```

**Account**: the characters on a player's account. This needs the player to sign in with Battle.net, so it takes
the authorization code from your OAuth redirect (requested with the `wow.profile` scope) instead of using your
client credentials:

```php
$account = $factory->createAccountService()->getCharacters($_GET['code'], 'https://example.com/callback');
```

### Error handling

Every failed request throws a `Fusani\Blizzard\Exception\BlizzardException`. Three subclasses cover the common cases:

- `NotFoundException` for a 404, such as a character that doesn't exist;
- `AuthenticationException` for a 401 that's still a 401 after the SDK gets a new token, which usually means the
  credentials are wrong;
- `RateLimitException` for a 429, when the client has gone over Blizzard's limit of 100 requests a second or 36,000
  an hour (and is still over it after any retries). `getRetryAfter()` returns how many seconds the server asked you
  to wait, or `null` if it didn't say.

The exception code is the HTTP status, or 0 if the API couldn't be reached. When the API or OAuth server explains the
error, the message includes its explanation, e.g. `GET /profile/user/wow returned 403 Forbidden: Account is not
linked`. `getResponseBody()` returns the decoded JSON error body, such as
`['code' => 403, 'type' => 'BLZWEBAPI00000403', 'detail' => '...']`, or `null` if the server didn't respond or the
body wasn't JSON.

```php
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;
use Fusani\Blizzard\Exception\RateLimitException;

try {
    $pets = $factory->createPetService()->getForCharacter($name, $realm);
} catch (NotFoundException) {
    $pets = null;
} catch (RateLimitException $e) {
    $tryAgainIn = $e->getRetryAfter() ?? 60; // seconds
} catch (BlizzardException $e) {
    error_log($e->getMessage());
}
```

## Development

```bash
composer install
composer check           # Pint, PHPStan (level max) and PHPUnit, the same checks CI runs
composer test:coverage   # coverage report (needs PCOV or Xdebug)
```

`composer test:live` runs a separate suite against the real API. It's skipped unless `BLIZZARD_CLIENT_ID` and
`BLIZZARD_CLIENT_SECRET` are set. The character endpoints also need `BLIZZARD_TEST_REALM` and
`BLIZZARD_TEST_CHARACTER`, and `BLIZZARD_TEST_REGION` picks the region (`us` by default). You can set them in a
local `phpunit.xml`, which git ignores.

See [CONTRIBUTING.md](CONTRIBUTING.md) for how changes and releases work, and [CHANGELOG.md](CHANGELOG.md) for
what's changed, including how to upgrade from 1.x.

## License

[MIT](LICENSE)
