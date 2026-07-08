# Customization

Fellowship is designed to be customizable while keeping the default setup simple.

## Custom Fellowship model

Publish the config:

```bash
php artisan vendor:publish --tag=fellowship-config
```

Create your custom model:

```php
<?php

namespace App\Models;

use EloquentWorks\Fellowship\Models\Fellowship as BaseFellowship;

class Fellowship extends BaseFellowship
{
    // Add custom scopes, relationships, casts, or helpers.
}
```

Then update `config/fellowship.php`:

```php
'models' => [
    'fellowship' => App\Models\Fellowship::class,
],
```

## Custom table names

Update the config:

```php
'tables' => [
    'users' => 'users',
    'friendships' => 'friendships',
],
```

If you change the `friendships` table name, make sure your migration creates the same table.

## Custom routes

```php
Route::fellowship([
    'prefix' => 'connections',
    'name' => 'connections.',
    'middleware' => ['web', 'auth', 'verified'],
]);
```

## Custom controller

```php
Route::fellowship([
    'controller' => App\Http\Controllers\ConnectionController::class,
]);
```

Example controller:

```php
<?php

namespace App\Http\Controllers;

use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;

class ConnectionController extends FellowshipController
{
    public function send(\Illuminate\Http\Request $request, mixed $user): \Illuminate\Http\RedirectResponse
    {
        parent::send($request, $user);

        return back()->with('status', 'Your connection request was sent.');
    }
}
```

## Custom middleware

```php
Route::fellowship([
    'middleware' => ['web', 'auth', 'verified', 'throttle:60,1'],
]);
```

## Disable events

```php
'dispatch_events' => false,
```

## Disable expiration

```php
'expires_after_days' => null,
```

## Disable cooldowns

```php
'request_cooldown_days' => null,
```

or:

```php
'request_cooldown_days' => 0,
```

## Add your own statuses

The package ships with these statuses:

```php
Status::PENDING;
Status::ACCEPTED;
Status::DENIED;
Status::BLOCKED;
Status::CANCELED;
Status::EXPIRED;
```

If your application needs extra states, prefer adding columns to your custom model/table instead of changing the core status flow.
