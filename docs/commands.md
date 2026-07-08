# Commands

Fellowship includes Artisan commands for installation and maintenance.

## Install command

```bash
php artisan fellowship:install
```

This publishes:

- The config file
- The migrations

Overwrite existing published files:

```bash
php artisan fellowship:install --force
```

Publish the optional route snippet:

```bash
php artisan fellowship:install --routes
```

The command also prints the route snippet:

```php
use Illuminate\Support\Facades\Route;

Route::fellowship();
```

## Expire pending requests

```bash
php artisan fellowships:expire
```

This command finds pending fellowship requests where `expires_at` is in the past and marks them as expired.

## Chunk size

The expiration command processes records in chunks.

Default:

```bash
php artisan fellowships:expire
```

Custom chunk size:

```bash
php artisan fellowships:expire --chunk=500
```

## Scheduling expiration

Add this to `routes/console.php` or your scheduler setup:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('fellowships:expire')->hourly();
```

Or daily:

```php
Schedule::command('fellowships:expire')->daily();
```

## Exit codes

Both commands return Laravel's normal success exit code when completed successfully.
