# Changelog

All notable changes to this package are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/). [CONTRIBUTING.md](CONTRIBUTING.md) says what counts as a breaking change.

## [Unreleased]

### Added

- `ServiceFactory` takes optional `$timeout` and `$connectTimeout` arguments, in seconds, for API and OAuth requests.

### Fixed

- API and OAuth requests time out after 10 seconds (5 seconds to connect) instead of waiting forever when Blizzard's servers stall. A timed-out request throws a `BlizzardException` with code 0.

## [2.0.0] - 2026-10-13

2.0.0 modernizes the SDK for current PHP and the current Blizzard OAuth flow. Most of it is breaking: see **Upgrading from 1.x** below.

### Upgrading from 1.x

- PHP 8.5 or newer is required (1.x allowed PHP 7.0), along with the `mbstring` extension.
- `ServiceFactory` takes the credentials once, in its constructor, instead of in every `create*Service()` call:

  ```php
  // 1.x
  $factory = new ServiceFactory();
  $realms = $factory->createRealmService($clientId, $clientSecret, $accessToken);

  // 2.0
  $factory = new ServiceFactory($clientId, $clientSecret);
  $realms = $factory->createRealmService();
  ```

  The SDK gets and renews access tokens itself, so there's no access token to pass in.
- Regions are `Region` enum values instead of strings: `$realms->getAllRealmsByRegion(Region::Eu)`. To convert a stored string, use `Region::from(strtolower($region))`.
- Errors throw exceptions instead of returning `false` or an array with `success` and `statusCode`. Catch `Fusani\Blizzard\Exception\BlizzardException`, or `NotFoundException` and `AuthenticationException` for a 404 and a 401.
- Responses no longer include an `access_token` key.

### Added

- `Region` enum for the US, EU, Korea, Taiwan and China regions, each with its own API and OAuth hosts and namespaces.
- `OAuth\TokenProvider`, which gets a client-credentials token on first use and reuses it until it expires. Pass a PSR-16 cache to `ServiceFactory` to share the token between requests.
- An optional `locale` (e.g. `en_US`) on `ServiceFactory`, sent with every request, so names come back as strings instead of every translation.
- `Item` methods take a region (they were US-only), defaulting to `Region::Us`.
- Typed exceptions in `Fusani\Blizzard\Exception`: `BlizzardException`, `AuthenticationException` and `NotFoundException`. The exception code is the HTTP status, or 0 for a connection failure.
- `declare(strict_types=1)` and native types throughout, with PHPStan at level max.
- A README, the MIT license and this changelog.

### Changed

- **Breaking:** requires PHP `^8.5`, `ext-mbstring` and Guzzle `^8.2` (1.x used Guzzle 6).
- **Breaking:** `ServiceFactory` takes `($clientId, $clientSecret, ?CacheInterface $cache = null, ?string $locale = null)`, and its `create*Service()` methods take no arguments.
- **Breaking:** services are built from `(Adapter $apiAdapter, TokenProvider $tokens, ?string $locale = null)`.
- **Breaking:** region parameters take a `Region` instead of a string.
- **Breaking:** `Reputation::getReputationTier()` takes an `int`.
- **Breaking:** a response that isn't valid JSON throws instead of returning an empty array.
- Tokens come from the current OAuth hosts (`oauth.battle.net`, and `oauth.battlenet.com.cn` for China).
- A request that gets a 401 gets a new token and is retried once.
- Character names and realm slugs are lowercased before they're sent, since the profile API returns a 404 for capitalized names.

### Removed

- **Breaking:** `Client`, the top-level `Fusani\Blizzard\WorldOfWarcraft` class and `Adapter\AdapterStub`.
- **Breaking:** `Adapter::put()` and `Adapter::changeBaseUrl()`. The API is read-only, and changing the base URL sent later requests to the wrong region.

### Fixed

- `Mount::getMountsForCharacter()` called the retired Community API. It now calls the profile API's mounts collection.
- An adapter used for one region no longer sends later requests to that region's host.
- `post()` sends its headers, including `Authorization`.

## [1.0.0] - 2024-11-18

Earlier versions have no changelog.

[Unreleased]: https://github.com/lokisapocalypse/blizzard-api-sdk/compare/2.0.0...development
[2.0.0]: https://github.com/lokisapocalypse/blizzard-api-sdk/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/lokisapocalypse/blizzard-api-sdk/releases/tag/1.0.0
