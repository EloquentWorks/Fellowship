# Release Notes

## v1.0.2 - Stability, Legacy Aliases, and Developer Quality

This release improves package stability before larger feature work.

It focuses on backwards compatibility, model naming cleanup, typed method signatures, stronger tests, code style tooling, and static analysis support.

---

## Added

- Added legacy namespace aliases for `Hootlex\Friendships`.
- Added a legacy alias for `Hootlex\Friendships\Status`.
- Added a clean `FriendshipGroup` model name.
- Kept the old `FriendFriendshipGroups` model name as a backwards-compatible class.
- Added return types to public friendship APIs.
- Added better PHPUnit tests for aliases, model naming, and return types.
- Added Laravel Pint for code style checks.
- Added PHPStan/Larastan for static analysis.
- Added Composer scripts for formatting, analysis, tests, and CI.

---

## Changed

- Updated friendship internals to use the cleaner `FriendshipGroup` model.
- Kept old APIs working while improving type clarity.
- Updated the GitHub Actions workflow to run:
  - Composer validation
  - Pint style checks
  - PHPStan/Larastan static analysis
  - PHPUnit tests

---

## Legacy Namespace Support

Existing applications may still use the old namespace:

```php
use Hootlex\Friendships\Traits\Friendable;
use Hootlex\Friendships\Status;
```

New applications should use:

```php
use PixelError\Friendships\Traits\Friendable;
use PixelError\Friendships\Status;
```

This release keeps the old namespace working through Composer-loaded aliases.

---

## Developer Commands

Run the test suite:

```bash
composer test
```

Run Laravel Pint:

```bash
composer format
```

Check formatting without changing files:

```bash
composer format:test
```

Run PHPStan/Larastan:

```bash
composer analyse
```

Run the full local CI stack:

```bash
composer ci
```

---

## Compatibility

| PHP | Laravel / Illuminate |
| --- | --- |
| PHP 8.2+ | Laravel 12 |
| PHP 8.3+ | Laravel 12 / 13 |

---

## Upgrade Notes

After updating, regenerate Composer autoload files:

```bash
composer dump-autoload
```

If you publish config or migrations, compare your existing files before overwriting local changes.

---

## Testing Recommended

Before production use, test:

- Legacy `Hootlex\Friendships` imports
- New `PixelError\Friendships` imports
- Friend requests
- Friend acceptance
- Friend denial
- Blocking and unblocking
- Friend groups
- Pagination
- Mutual friends
