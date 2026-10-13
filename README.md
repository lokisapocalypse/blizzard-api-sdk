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

Every failed request throws a `Fusani\Blizzard\Exception\BlizzardException`. Two subclasses cover the common cases:

- `NotFoundException` for a 404, such as a character that doesn't exist;
- `AuthenticationException` for a 401 that's still a 401 after the SDK gets a new token, which usually means the
  credentials are wrong.

The exception code is the HTTP status, or 0 if the API couldn't be reached.

```php
use Fusani\Blizzard\Exception\BlizzardException;
use Fusani\Blizzard\Exception\NotFoundException;

try {
    $pets = $factory->createPetService()->getForCharacter($name, $realm);
} catch (NotFoundException) {
    $pets = null;
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
