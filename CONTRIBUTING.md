# Contributing

## Development

```bash
composer install
composer check        # Pint, PHPStan (level max) and PHPUnit, the same checks CI runs
composer test:live    # optional: calls the real API (see the README for the environment variables)
```

Branch from `development` and open pull requests against it. CI must pass before a PR can merge. Add a line to the
`Unreleased` section of [CHANGELOG.md](CHANGELOG.md) for anything a user of the package would notice.

## Releasing

### Versioning

Releases follow [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`.

**Major** for any change that can break code that uses the package:

- raising the minimum PHP version, or requiring a new PHP extension;
- removing or renaming a public class, method, constant or enum case;
- changing a public method's parameters (adding a required one, changing a type, reordering them);
- changing a return type, or the exceptions a method throws;
- changing a default that changes behavior, such as a method's default region.

**Minor** for new features that don't break anything:

- new services, methods or enum cases;
- new optional parameters at the end of a method;
- supporting a new PHP version.

**Patch** for everything else: bug fixes, documentation, internal refactoring and dev-only dependencies.

The SDK returns the API's responses as arrays, so a change Blizzard makes to a response's shape isn't a change to the
package's version.

### Tags

- Tags are the bare version with no `v` prefix (`2.0.0`, not `v2.0.0`), the same as every earlier tag.
- Tags are annotated (`git tag -a`). The release workflow refuses lightweight tags.
- Tags are made on `development`. There's no separate release branch.
- Pre-releases add `-RC1`, `-RC2` and so on (`2.0.0-RC1`). Use one before a major version, or to test a change to
  the release workflow. Composer only installs them for someone who asks for `RC` stability.

The 34 tags from `0.0.1` to `1.0.0` were made before these rules. They're lightweight, have no GitHub Releases, and
aren't numbered consistently (`0.2` has two parts, and `0.3.5` and `0.3.12` were tagged before `0.3.3` and
`0.3.10`). They stay as they are, because people may have pinned to them.

### Release notes

[CHANGELOG.md](CHANGELOG.md) follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Pushing a tag runs
[the release workflow](.github/workflows/release.yml), which creates the GitHub Release with the tag's section of the
changelog as its notes:

- a release (`2.0.0`) uses the `## [2.0.0]` section, and fails if there isn't one;
- a pre-release (`2.0.0-RC1`) uses the `## [Unreleased]` section, and is marked as a pre-release on GitHub.

Don't hard-wrap lines in the changelog: write each paragraph and list item on one line. GitHub Releases show every
newline as a line break, so a wrapped line breaks mid-sentence in the release notes.

Packagist picks up new tags through its GitHub webhook.

### Steps

1. Check that everything for the release is merged into `development` and CI passes there.
2. Pick the version from the rules above.
3. Open a PR against `development` that renames `## [Unreleased]` in the changelog to `## [X.Y.Z] - YYYY-MM-DD`,
   adds an empty `## [Unreleased]` above it, and updates the compare links at the bottom. Merge it.
4. Tag the merged commit and push the tag:

   ```bash
   git checkout development
   git pull
   git tag -a X.Y.Z -m X.Y.Z
   git push origin X.Y.Z
   ```

5. Check that [the release workflow](https://github.com/lokisapocalypse/blizzard-api-sdk/actions/workflows/release.yml) passed, that the
   [GitHub Release](https://github.com/lokisapocalypse/blizzard-api-sdk/releases) has the right notes, and that
   [Packagist](https://packagist.org/packages/fusani/blizzard) lists the version.

For a pre-release, skip step 3 and tag `X.Y.Z-RCn` instead.
