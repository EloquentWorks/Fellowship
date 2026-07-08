# Installation

This guide walks through installing Fellowship in a Laravel application.

## Install with Composer

```bash
composer require eloquentworks/fellowship
```

## Publish package files

Run the install command:

```bash
php artisan fellowship:install
```

This publishes:

- The Fellowship config file
- The Fellowship migrations

To also publish a copyable route snippet:

```bash
php artisan fellowship:install --routes
```

To overwrite already published files:

```bash
php artisan fellowship:install --force
```

You may also publish files manually:

```bash
php artisan vendor:publish --tag=fellowship-config
php artisan vendor:publish --tag=fellowship-migrations
php artisan vendor:publish --tag=fellowship-routes
```

## Run migrations

```bash
php artisan migrate
```

## Add the trait to your user model

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

Your model must be an Eloquent model and should be the same model configured in Laravel's auth provider.

## Register routes

Fellowship routes are opt-in. Add the router macro call to `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::fellowship();
```

## Verify installation

You can verify that routes exist with:

```bash
php artisan route:list --name=fellowship
```

You should see route names such as:

```text
fellowship.requests.store
fellowship.requests.accept
fellowship.blocks.store
```
