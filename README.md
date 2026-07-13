[![tests](https://github.com/EloquentWorks/Fellowship/actions/workflows/tests.yml/badge.svg)](https://github.com/EloquentWorks/Fellowship/actions/workflows/tests.yml)

# Laravel Fellowship

Elegant friendship and social connection tools for Laravel applications.

Laravel Fellowship gives your Eloquent user model a clean API for friend requests, accepted friendships, blocks, mutual friends, request expiration, cooldowns, web routes, events, and Artisan commands.

```php
$user->sendFriendRequestTo($otherUser);

$otherUser->acceptFriendRequestFrom($user);

$user->isFriendsWith($otherUser);
```

## Supported Versions

| Package Version | PHP | Laravel / Illuminate |
| --- | --- | --- |
| Current | `^8.2` | `^12.0 \|\| ^13.0` |

> Laravel 12 supports PHP 8.2+. Laravel 13 requires PHP 8.3+. Composer will automatically resolve compatible versions based on your project.

## Installation

Install the package through Composer:

```bash
composer require eloquent-works/fellowship
```

Publish the config and migrations:

```bash
php artisan fellowship:install
```

Or publish the optional route snippet too:

```bash
php artisan fellowship:install --routes
```

Run your migrations:

```bash
php artisan migrate
```

## Features

- Send, accept, deny, cancel, and expire friend requests
- Remove accepted friends
- Block and unblock users
- Incoming and outgoing request collections
- Friends and blocked-user collections
- Mutual friends and mutual friend counts
- Request expiration and resend cooldowns
- Lifecycle events for notifications, logs, and activity feeds
- Optional web routes with `Route::fellowship()`
- Install and cleanup Artisan commands
- Configurable model and table names

## Add the trait to your user model

Add `HasFellowships` to your application user model:

```php
<?php

namespace App\Models;

use EloquentWorks\Fellowship\Traits\HasFellowships;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFellowships;
}
```

## Register web routes

Fellowship does not load web routes automatically. Add this to `routes/web.php` when you want the package routes:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::fellowship();
```

Custom route options:

```php
Route::fellowship([
    'prefix' => 'connections',
    'name' => 'connections.',
    'middleware' => ['web', 'auth', 'verified'],
]);
```

Default routes:

| Method | URI | Name | Action |
| --- | --- | --- | --- |
| POST | `/fellowship/{user}/request` | `fellowship.requests.store` | Send request |
| POST | `/fellowship/{user}/accept` | `fellowship.requests.accept` | Accept request |
| POST | `/fellowship/{user}/deny` | `fellowship.requests.deny` | Deny request |
| DELETE | `/fellowship/{user}/cancel` | `fellowship.requests.cancel` | Cancel request |
| DELETE | `/fellowship/{user}/remove` | `fellowship.friends.remove` | Remove friend |
| POST | `/fellowship/{user}/block` | `fellowship.blocks.store` | Block user |
| DELETE | `/fellowship/{user}/unblock` | `fellowship.blocks.destroy` | Unblock user |

## Basic usage

```php
// Send a request
$authUser->sendFriendRequestTo($otherUser);

// Accept a request
$otherUser->acceptFriendRequestFrom($authUser);

// Deny a request
$otherUser->denyFriendRequestFrom($authUser);

// Cancel a sent request
$authUser->cancelFriendRequestTo($otherUser);

// Remove a friend
$authUser->removeFriend($otherUser);

// Block and unblock
$authUser->blockUser($otherUser);
$authUser->unblockUser($otherUser);
```

## Query helpers

```php
$user->friends();

$user->incomingFriendRequests();

$user->outgoingFriendRequests();

$user->blockedUsers();

$user->blockedByUsers();

$user->mutualFriendsWith($otherUser);

$user->mutualFriendsCountWith($otherUser);

$user->friendsCount();
```

## Status helpers

```php
$user->isFriendsWith($otherUser);

$user->hasBlocked($otherUser);

$user->isBlockedBy($otherUser);

$user->hasPendingFriendRequestWith($otherUser);

$user->friendshipStatusWith($otherUser);

$user->friendshipWith($otherUser);

$user->canSendFriendRequestTo($otherUser);
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=fellowship-config
```

Important options:

```php
return [
    'models' => [
        'fellowship' => EloquentWorks\Fellowship\Models\Fellowship::class,
    ],

    'tables' => [
        'users' => 'users',
        'friendships' => 'friendships',
    ],

    'routes' => [
        'prefix' => 'fellowship',
        'middleware' => ['web', 'auth'],
        'name' => 'fellowship.',
    ],

    'expires_after_days' => 30,

    'request_cooldown_days' => 7,

    'dispatch_events' => true,
];
```

## Commands

Expire old pending requests:

```bash
php artisan fellowships:expire
```

Use a custom chunk size:

```bash
php artisan fellowships:expire --chunk=500
```

## Events

Fellowship dispatches lifecycle events when `dispatch_events` is enabled:

- `FellowshipRequestSent`
- `FellowshipRequestAccepted`
- `FellowshipRequestDenied`
- `FellowshipRequestCanceled`
- `FellowshipRequestExpired`
- `FellowshipRemoved`
- `UserBlocked`
- `UserUnblocked`

## Documentation

Full docs are available in the [`docs`](docs) directory:

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Routes](docs/routes.md)
- [Usage](docs/usage.md)
- [Events](docs/events.md)
- [Commands](docs/commands.md)
- [Customization](docs/customization.md)
- [Testing](docs/testing.md)
- [Upgrade Guide](docs/upgrade-guide.md)

## Security

If you discover a security vulnerability, please report it privately instead of opening a public issue.

## Credits

Built by Eloquent Works.

## License

The MIT License.
