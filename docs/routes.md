# Routes

Fellowship does not register web routes automatically. Routes are opt-in through a router macro.

## Basic route registration

Add this to `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::fellowship();
```

## Default routes

| Method | URI | Name | Controller Method |
| --- | --- | --- | --- |
| POST | `/fellowship/{user}/request` | `fellowship.requests.store` | `send` |
| POST | `/fellowship/{user}/accept` | `fellowship.requests.accept` | `accept` |
| POST | `/fellowship/{user}/deny` | `fellowship.requests.deny` | `deny` |
| DELETE | `/fellowship/{user}/cancel` | `fellowship.requests.cancel` | `cancel` |
| DELETE | `/fellowship/{user}/remove` | `fellowship.friends.remove` | `remove` |
| POST | `/fellowship/{user}/block` | `fellowship.blocks.store` | `block` |
| DELETE | `/fellowship/{user}/unblock` | `fellowship.blocks.destroy` | `unblock` |

## Custom prefix

```php
Route::fellowship([
    'prefix' => 'connections',
]);
```

This changes URLs from:

```text
/fellowship/{user}/request
```

to:

```text
/connections/{user}/request
```

## Custom route names

```php
Route::fellowship([
    'name' => 'connections.',
]);
```

This changes route names from:

```text
fellowship.requests.store
```

to:

```text
connections.requests.store
```

The trailing dot is optional. Fellowship normalizes it internally.

## Custom middleware

```php
Route::fellowship([
    'middleware' => ['web', 'auth', 'verified'],
]);
```

## Custom controller

```php
Route::fellowship([
    'controller' => App\Http\Controllers\ConnectionController::class,
]);
```

Your controller can extend the package controller:

```php
<?php

namespace App\Http\Controllers;

use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;

class ConnectionController extends FellowshipController
{
    // Override actions as needed.
}
```

## Generating links

```php
route('fellowship.requests.store', $user);
route('fellowship.requests.accept', $user);
route('fellowship.requests.deny', $user);
route('fellowship.requests.cancel', $user);
route('fellowship.friends.remove', $user);
route('fellowship.blocks.store', $user);
route('fellowship.blocks.destroy', $user);
```

## Example Blade buttons

```blade
<form method="POST" action="{{ route('fellowship.requests.store', $user) }}">
    @csrf
    <button type="submit">Add Friend</button>
</form>

<form method="POST" action="{{ route('fellowship.requests.accept', $user) }}">
    @csrf
    <button type="submit">Accept</button>
</form>

<form method="POST" action="{{ route('fellowship.blocks.store', $user) }}">
    @csrf
    <button type="submit">Block</button>
</form>

<form method="POST" action="{{ route('fellowship.friends.remove', $user) }}">
    @csrf
    @method('DELETE')
    <button type="submit">Remove Friend</button>
</form>
```
