# Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=fellowship-config
```

The config file is published to:

```text
config/fellowship.php
```

## Models

```php
'models' => [
    'fellowship' => EloquentWorks\Fellowship\Models\Fellowship::class,
],
```

Use this when you want to extend the default Fellowship model.

Example:

```php
'models' => [
    'fellowship' => App\Models\Fellowship::class,
],
```

Your custom model should extend the package model:

```php
<?php

namespace App\Models;

use EloquentWorks\Fellowship\Models\Fellowship as BaseFellowship;

class Fellowship extends BaseFellowship
{
    // Custom behavior...
}
```

## Tables

```php
'tables' => [
    'users' => 'users',
    'friendships' => 'friendships',
],
```

The `friendships` table stores pending requests, accepted friendships, denied requests, canceled requests, expired requests, and blocked relationships.

If you change the table names, update your published migration to match.

## Routes

```php
'routes' => [
    'prefix' => 'fellowship',
    'middleware' => ['web', 'auth'],
    'name' => 'fellowship.',
],
```

These defaults are used by:

```php
Route::fellowship();
```

You can override them directly when registering routes:

```php
Route::fellowship([
    'prefix' => 'connections',
    'middleware' => ['web', 'auth', 'verified'],
    'name' => 'connections.',
]);
```

You may also pass a custom controller:

```php
Route::fellowship([
    'controller' => App\Http\Controllers\ConnectionsController::class,
]);
```

## Request expiration

```php
'expires_after_days' => 30,
```

Pending requests expire after this many days.

Set it to `null` to disable expiration:

```php
'expires_after_days' => null,
```

## Request cooldown

```php
'request_cooldown_days' => 7,
```

This controls how long a user must wait before sending another friend request after a previous request was denied, canceled, or expired.

Disable cooldowns with:

```php
'request_cooldown_days' => null,
```

or:

```php
'request_cooldown_days' => 0,
```

## Events

```php
'dispatch_events' => true,
```

Set this to `false` if you do not want Fellowship to dispatch lifecycle events.

```php
'dispatch_events' => false,
```
