# Release Process

This document describes the release workflow for Laravel Persona.

## Before releasing

Verify that all of the following pass successfully:

``` bash
composer test
composer analyse
vendor/bin/pint --test
```

Ensure that:

-   Documentation has been updated.
-   CHANGELOG (if used) reflects the release.
-   Version constraints are correct.
-   `composer.json` metadata is up to date.
-   README examples match the current API.

## Create a release

1.  Commit all changes.
2.  Create a version tag.

``` bash
git tag v1.0.0
git push origin v1.0.0
```

3.  Create a GitHub Release using the new tag.
4.  Packagist will automatically detect the new release if GitHub
    integration is enabled.

## Release checklist

-   PHPUnit passes
-   PHPStan passes
-   Laravel Pint passes
-   No debug code remains
-   Documentation reviewed
-   New features include tests
-   Public API reviewed for backwards compatibility

## Versioning

Persona follows Semantic Versioning.

-   **MAJOR** --- breaking changes
-   **MINOR** --- new backwards-compatible features
-   **PATCH** --- bug fixes and documentation improvements

Examples:

-   `1.0.0`
-   `1.1.0`
-   `1.1.1`
-   `2.0.0`

## After release

-   Verify the package appears on Packagist.
-   Verify installation from a fresh Laravel project.
-   Announce the release if desired.
