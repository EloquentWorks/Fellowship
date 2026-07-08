# Testing

Fellowship is designed to work well with PHPUnit, Orchestra Testbench, Laravel Pint, and PHPStan.

## Run the package tests

```bash
vendor/bin/phpunit
```

If your `composer.json` has a script:

```bash
composer test
```

## Run PHPStan

```bash
vendor/bin/phpstan analyse
```

or:

```bash
composer analyse
```

## Run Laravel Pint

```bash
vendor/bin/pint
```

or:

```bash
composer format
```

## Testing routes

Fellowship routes are registered with the router macro:

```php
use Illuminate\Support\Facades\Route;

Route::fellowship();
```

You can assert routes exist:

```php
$route = Route::getRoutes()->getByName('fellowship.requests.store');

$this->assertNotNull($route);
$this->assertContains('POST', $route->methods());
$this->assertSame('fellowship/{user}/request', $route->uri());
```

## Testing authenticated web routes

Use Laravel's `actingAs()` helper:

```php
$this->actingAs($sender)
    ->post(route('fellowship.requests.store', $recipient))
    ->assertRedirect();
```

## Testbench app key

If you are testing routes with the `web` middleware, make sure your test app has an encryption key:

```php
$app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
```

## Testbench provider setup

Your package test case should load the service provider:

```php
use EloquentWorks\Fellowship\FellowshipServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FellowshipServiceProvider::class,
        ];
    }
}
```

## Testing events

```php
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use Illuminate\Support\Facades\Event;

Event::fake();

$sender->sendFriendRequestTo($recipient);

Event::assertDispatched(FellowshipRequestSent::class);
```

## Testing without events

```php
config()->set('fellowship.dispatch_events', false);

Event::fake();

$sender->sendFriendRequestTo($recipient);

Event::assertNotDispatched(FellowshipRequestSent::class);
```
