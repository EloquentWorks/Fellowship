# FAQ

## Does Fellowship register routes automatically?

No. Fellowship routes are opt-in.

Add this to `routes/web.php`:

```php
use Illuminate\Support\Facades\Route;

Route::fellowship();
```

## Do I need a facade?

No. The current routing API uses Laravel's router macro:

```php
Route::fellowship();
```

## Why do I need to add a trait to my user model?

The package methods live in the `HasFellowships` trait. Add it to your user model:

```php
use EloquentWorks\Fellowship\Traits\HasFellowships;

class User extends Authenticatable
{
    use HasFellowships;
}
```

## Can users send duplicate friend requests?

No. Fellowship prevents duplicate active pending requests.

## Can users send requests after denial or cancellation?

Yes, but only after the configured cooldown period if cooldowns are enabled.

```php
'request_cooldown_days' => 7,
```

## Can requests expire?

Yes. Pending requests expire based on:

```php
'expires_after_days' => 30,
```

Run the expiration command:

```bash
php artisan fellowships:expire
```

## Can users block each other?

Yes.

```php
$user->blockUser($otherUser);
$user->unblockUser($otherUser);
```

A blocked relationship prevents new friend requests between those users.

## How do I customize route names?

```php
Route::fellowship([
    'name' => 'connections.',
]);
```

This creates names like:

```text
connections.requests.store
connections.blocks.store
```

## How do I customize the URL prefix?

```php
Route::fellowship([
    'prefix' => 'connections',
]);
```

## How do I disable events?

```php
'dispatch_events' => false,
```
