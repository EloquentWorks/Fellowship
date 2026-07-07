[![Tests](https://github.com/user-error-pixel/laravel-friendships/actions/workflows/tests.yml/badge.svg)](https://github.com/user-error-pixel/laravel-friendships/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/user-pixel-error/laravel-friendships.svg)](https://packagist.org/packages/user-pixel-error/laravel-friendships)
[![Total Downloads](https://img.shields.io/packagist/dt/user-pixel-error/laravel-friendships.svg)](https://packagist.org/packages/user-pixel-error/laravel-friendships)
[![PHP Version](https://img.shields.io/packagist/php-v/user-pixel-error/laravel-friendships.svg)](https://packagist.org/packages/user-pixel-error/laravel-friendships)

# PixelError Laravel Friendships

PixelError Laravel Friendships is a Laravel package that allows Eloquent models to manage social relationships.

It can be used to build features like:

- Friend requests
- Accepted friendships
- Denied friend requests
- Blocking users
- Friend groups such as close friends, family, or acquaintances

This package is useful for social platforms, gaming communities, forums, dashboards, team systems, and applications where users need to connect with each other.

---

## Table of Contents

- [Supported Versions](#supported-versions)
- [Features](#features)
- [Installation](#installation)
- [Publish Configuration](#publish-configuration)
- [Publish Migrations](#publish-migrations)
- [Setup a Model](#setup-a-model)
- [Basic Usage](#basic-usage)
- [Send a Friend Request](#send-a-friend-request)
- [Accept a Friend Request](#accept-a-friend-request)
- [Deny a Friend Request](#deny-a-friend-request)
- [Remove a Friend](#remove-a-friend)
- [Block a Model](#block-a-model)
- [Unblock a Model](#unblock-a-model)
- [Checking Friendship Status](#checking-friendship-status)
  - [Check if Two Models Are Friends](#check-if-two-models-are-friends)
  - [Check if a User Has a Pending Friend Request From Another User](#check-if-a-user-has-a-pending-friend-request-from-another-user)
  - [Check if a User Has Sent a Friend Request](#check-if-a-user-has-sent-a-friend-request)
  - [Check if a User Has Blocked Another User](#check-if-a-user-has-blocked-another-user)
  - [Check if a User Is Blocked By Another User](#check-if-a-user-is-blocked-by-another-user)
- [Retrieving Friendships](#retrieving-friendships)
  - [Get a Single Friendship](#get-a-single-friendship)
  - [Get All Friendships](#get-all-friendships)
  - [Get Pending Friendships](#get-pending-friendships)
  - [Get Accepted Friendships](#get-accepted-friendships)
  - [Get Denied Friendships](#get-denied-friendships)
  - [Get Blocked Friendships](#get-blocked-friendships)
  - [Get Friend Requests](#get-friend-requests)
- [Retrieving Friends](#retrieving-friends)
  - [Get Friends Collection](#get-friends-collection)
  - [Get Friends With Pagination](#get-friends-with-pagination)
- [Friend Groups](#friend-groups)
  - [Add a Friend to a Group](#add-a-friend-to-a-group)
  - [Remove a Friend From a Specific Group](#remove-a-friend-from-a-specific-group)
  - [Remove a Friend From All Groups](#remove-a-friend-from-all-groups)
- [Example Controller Usage](#example-controller-usage)
- [Example Routes](#example-routes)
- [Example Blade Usage](#example-blade-usage)
  - [Send Friend Request Button](#send-friend-request-button)
  - [Accept or Deny Friend Request Buttons](#accept-or-deny-friend-request-buttons)
  - [Remove Friend Button](#remove-friend-button)
  - [Block User Button](#block-user-button)
- [Common Friendship Flow](#common-friendship-flow)
- [Notes](#notes)
- [Testing](#testing)
- [License](#license)

---

## Supported Versions

| Package Version | PHP | Laravel / Illuminate |
| --- | --- | --- |
| Current | `^8.2` | `^12.0 \|\| ^13.0` |

> Laravel 12 supports PHP 8.2+. Laravel 13 requires PHP 8.3+. Composer will automatically resolve compatible versions based on your project.

---

## Features

Models using this package can:

- Send friend requests
- Accept friend requests
- Deny friend requests
- Remove friends
- Block other models
- Unblock other models
- Check friendship status
- Check pending friend requests
- Retrieve all friendships
- Retrieve pending friendships
- Retrieve accepted friendships
- Retrieve denied friendships
- Retrieve blocked friendships
- Retrieve friend requests
- Retrieve friends as a collection or paginated list
- Assign friends to groups
- Remove friends from groups

---

## Installation

Install the package using Composer:

```bash
composer require user-pixel-error/laravel-friendships
```

Laravel should automatically discover the package service provider.

---

## Publish Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --provider="PixelError\Friendships\FriendshipsServiceProvider" --tag="friendships-config"
```

This will create the config file at:

```text
config/friendships.php
```

The config file controls the table names and default friendship groups.

Example:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Friendship Tables
    |--------------------------------------------------------------------------
    |
    | These table names are used by the package to store friendship data.
    | You can change these values if your application uses different table names.
    |
    */

    'tables' => [

        // Stores the main friendship relationships between users.
        'fr_pivot' => 'friendships',

        // Stores which friendship groups a user has assigned to their friends.
        'fr_groups_pivot' => 'user_friendship_groups',
    ],

    /*
    |--------------------------------------------------------------------------
    | Friendship Groups
    |--------------------------------------------------------------------------
    |
    | These are the default friendship groups available in the package.
    | The numeric values are stored in the database, so avoid changing them
    | after users have already started using groups.
    |
    */

    'groups' => [

        // General friends or people the user knows casually.
        'acquaintances' => 0,

        // Friends the user considers closer than normal acquaintances.
        'close_friends' => 1,

        // Friends marked as family members.
        'family' => 2,
    ],

];
```

---

## Publish Migrations

Publish the package migrations:

```bash
php artisan vendor:publish --provider="PixelError\Friendships\FriendshipsServiceProvider" --tag="friendships-migrations"
```

Then run the migrations:

```bash
php artisan migrate
```

This will create the database tables used to store friendships and friendship groups.

---

## Setup a Model

Add the `Friendable` trait to any Eloquent model that should be able to make friends.

Most applications will add it to the `User` model.

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PixelError\Friendships\Traits\Friendable;

class User extends Authenticatable
{
    use Friendable;

    // ...
}
```

After adding the trait, your model can send friend requests, accept requests, block users, retrieve friends, and manage friendship groups.

---

## Basic Usage

Assume you have two users:

```php
$user = User::find(1);
$recipient = User::find(2);
```

---

## Send a Friend Request

Use `befriend()` to send a friend request to another model.

```php
$user->befriend($recipient);
```

Example:

```php
$sender = auth()->user();
$recipient = User::findOrFail($id);

$sender->befriend($recipient);
```

This creates a pending friendship request from `$sender` to `$recipient`.

---

## Accept a Friend Request

The recipient can accept a pending friend request using `acceptFriendRequest()`.

```php
$recipient->acceptFriendRequest($sender);
```

Example:

```php
$user = auth()->user();
$sender = User::findOrFail($id);

$user->acceptFriendRequest($sender);
```

After accepting, both users are considered friends.

---

## Deny a Friend Request

Use `denyFriendRequest()` to reject a pending friend request.

```php
$recipient->denyFriendRequest($sender);
```

Example:

```php
$user = auth()->user();
$sender = User::findOrFail($id);

$user->denyFriendRequest($sender);
```

---

## Remove a Friend

Use `unfriend()` to remove an accepted friendship.

```php
$user->unfriend($friend);
```

Example:

```php
$user = auth()->user();
$friend = User::findOrFail($id);

$user->unfriend($friend);
```

After this, the users are no longer friends.

---

## Block a Model

Use `blockFriend()` to block another model.

```php
$user->blockFriend($friend);
```

Example:

```php
$user = auth()->user();
$blockedUser = User::findOrFail($id);

$user->blockFriend($blockedUser);
```

You can use this for blocking users from sending friend requests, appearing in friend lists, or interacting with the current user depending on how your application handles blocked users.

---

## Unblock a Model

Use `unblockFriend()` to remove a block.

```php
$user->unblockFriend($friend);
```

Example:

```php
$user = auth()->user();
$blockedUser = User::findOrFail($id);

$user->unblockFriend($blockedUser);
```

---

## Checking Friendship Status

### Check if Two Models Are Friends

```php
$user->isFriendWith($friend);
```

Example:

```php
if ($user->isFriendWith($friend)) {
    // Users are friends.
}
```

---

### Check if a User Has a Pending Friend Request From Another User

```php
$user->hasFriendRequestFrom($sender);
```

Example:

```php
if ($user->hasFriendRequestFrom($sender)) {
    // The sender has already sent this user a friend request.
}
```

---

### Check if a User Has Sent a Friend Request

```php
$user->hasSentFriendRequestTo($recipient);
```

Example:

```php
if ($user->hasSentFriendRequestTo($recipient)) {
    // The current user already sent a request to this recipient.
}
```

---

### Check if a User Has Blocked Another User

```php
$user->hasBlocked($friend);
```

Example:

```php
if ($user->hasBlocked($friend)) {
    // The current user has blocked this model.
}
```

---

### Check if a User Is Blocked By Another User

```php
$user->isBlockedBy($friend);
```

Example:

```php
if ($user->isBlockedBy($friend)) {
    // The other user has blocked the current user.
}
```

---

## Retrieving Friendships

### Get a Single Friendship

```php
$user->getFriendship($friend);
```

Example:

```php
$friendship = $user->getFriendship($friend);
```

This returns the friendship record between the two models.

---

### Get All Friendships

```php
$user->getAllFriendships();
```

Example:

```php
$friendships = auth()->user()->getAllFriendships();
```

This returns every friendship record connected to the user, including pending, accepted, denied, and blocked friendships.

---

### Get Pending Friendships

```php
$user->getPendingFriendships();
```

Example:

```php
$pending = auth()->user()->getPendingFriendships();
```

Use this when you want to show friendships that have not been accepted or denied yet.

---

### Get Accepted Friendships

```php
$user->getAcceptedFriendships();
```

Example:

```php
$accepted = auth()->user()->getAcceptedFriendships();
```

Use this when you want to retrieve only confirmed friendships.

---

### Get Denied Friendships

```php
$user->getDeniedFriendships();
```

Example:

```php
$denied = auth()->user()->getDeniedFriendships();
```

---

### Get Blocked Friendships

```php
$user->getBlockedFriendships();
```

Example:

```php
$blocked = auth()->user()->getBlockedFriendships();
```

---

### Get Friend Requests

```php
$user->getFriendRequests();
```

Example:

```php
$requests = auth()->user()->getFriendRequests();
```

Use this to show incoming friend requests for the authenticated user.

---

## Retrieving Friends

### Get Friends Collection

```php
$user->getFriends();
```

Example:

```php
$friends = auth()->user()->getFriends();

foreach ($friends as $friend) {
    echo $friend->name;
}
```

---

### Get Friends With Pagination

You can pass a number to paginate the friend list.

```php
$user->getFriends($perPage = 20);
```

Example:

```php
$friends = auth()->user()->getFriends(20);
```

In a Blade view:

```blade
@foreach ($friends as $friend)
    <div>
        {{ $friend->name }}
    </div>
@endforeach

{{ $friends->links() }}
```

---

## Friend Groups

Friend groups allow users to organize friends into categories.

Default groups are defined in `config/friendships.php`:

```php
'groups' => [
    'acquaintances' => 0,
    'close_friends' => 1,
    'family' => 2,
],
```

---

### Add a Friend to a Group

```php
$user->groupFriend($friend, 'close_friends');
```

Example:

```php
$user = auth()->user();
$friend = User::findOrFail($id);

$user->groupFriend($friend, 'close_friends');
```

---

### Remove a Friend From a Specific Group

```php
$user->ungroupFriend($friend, 'close_friends');
```

Example:

```php
$user->ungroupFriend($friend, 'close_friends');
```

---

### Remove a Friend From All Groups

```php
$user->ungroupFriend($friend);
```

Example:

```php
$user = auth()->user();
$friend = User::findOrFail($id);

$user->ungroupFriend($friend);
```

---

## Example Controller Usage

Here is an example controller showing how you might use the package in a Laravel application.

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FriendController extends Controller
{
    public function sendRequest(User $user): RedirectResponse
    {
        auth()->user()->befriend($user);

        return back()->with('status', 'Friend request sent.');
    }

    public function acceptRequest(User $user): RedirectResponse
    {
        auth()->user()->acceptFriendRequest($user);

        return back()->with('status', 'Friend request accepted.');
    }

    public function denyRequest(User $user): RedirectResponse
    {
        auth()->user()->denyFriendRequest($user);

        return back()->with('status', 'Friend request denied.');
    }

    public function removeFriend(User $user): RedirectResponse
    {
        auth()->user()->unfriend($user);

        return back()->with('status', 'Friend removed.');
    }

    public function block(User $user): RedirectResponse
    {
        auth()->user()->blockFriend($user);

        return back()->with('status', 'User blocked.');
    }

    public function unblock(User $user): RedirectResponse
    {
        auth()->user()->unblockFriend($user);

        return back()->with('status', 'User unblocked.');
    }
}
```

---

## Example Routes

```php
use App\Http\Controllers\FriendController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/friends/{user}/request', [FriendController::class, 'sendRequest'])
        ->name('friends.request');

    Route::post('/friends/{user}/accept', [FriendController::class, 'acceptRequest'])
        ->name('friends.accept');

    Route::post('/friends/{user}/deny', [FriendController::class, 'denyRequest'])
        ->name('friends.deny');

    Route::delete('/friends/{user}', [FriendController::class, 'removeFriend'])
        ->name('friends.remove');

    Route::post('/friends/{user}/block', [FriendController::class, 'block'])
        ->name('friends.block');

    Route::delete('/friends/{user}/block', [FriendController::class, 'unblock'])
        ->name('friends.unblock');
});
```

---

## Example Blade Usage

### Send Friend Request Button

```blade
<form method="POST" action="{{ route('friends.request', $user) }}">
    @csrf

    <button type="submit" class="btn btn-primary">
        Add Friend
    </button>
</form>
```

---

### Accept or Deny Friend Request Buttons

```blade
<form method="POST" action="{{ route('friends.accept', $user) }}">
    @csrf

    <button type="submit" class="btn btn-success">
        Accept
    </button>
</form>

<form method="POST" action="{{ route('friends.deny', $user) }}">
    @csrf

    <button type="submit" class="btn btn-secondary">
        Deny
    </button>
</form>
```

---

### Remove Friend Button

```blade
<form method="POST" action="{{ route('friends.remove', $user) }}">
    @csrf
    @method('DELETE')

    <button type="submit" class="btn btn-danger">
        Remove Friend
    </button>
</form>
```

---

### Block User Button

```blade
<form method="POST" action="{{ route('friends.block', $user) }}">
    @csrf

    <button type="submit" class="btn btn-warning">
        Block User
    </button>
</form>
```

---

## Common Friendship Flow

A normal friend request flow usually looks like this:

```php
$alice->befriend($bob);
$bob->acceptFriendRequest($alice);

$alice->isFriendWith($bob); // true
$bob->isFriendWith($alice); // true
```

A denied request flow:

```php
$alice->befriend($bob);
$bob->denyFriendRequest($alice);
```

A block flow:

```php
$alice->blockFriend($bob);

$alice->hasBlocked($bob); // true
$bob->isBlockedBy($alice); // true
```

---

## Notes

- Add the `Friendable` trait only to models that should be able to manage friendships.
- Friendship groups are configured in `config/friendships.php`.
- Be careful changing group numeric values after your application is already using the package.
- You should run your test suite after installing or upgrading the package.
- For production applications, always validate that users cannot send duplicate requests or interact with users who have blocked them.

---

## Testing

Run the package test suite with:

```bash
vendor/bin/phpunit
```

If your project uses Composer scripts, you may also be able to run:

```bash
composer test
```

---

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
