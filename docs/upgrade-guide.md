# Upgrade Guide

## From `Fellowship::routes()` to `Route::fellowship()`

Older versions of the package used a facade-style API:

```php
use EloquentWorks\Fellowship\Facades\Fellowship;

Fellowship::routes();
```

The current routing API uses a Laravel router macro:

```php
use Illuminate\Support\Facades\Route;

Route::fellowship();
```

## Remove the facade alias

If your application or package config still contains a facade alias, remove it:

```json
"aliases": {
    "Fellowship": "EloquentWorks\\Fellowship\\Facades\\Fellowship"
}
```

The facade is no longer required.

## Update route config comments

Update any documentation or config comments that mention:

```php
Fellowship::routes();
```

to:

```php
Route::fellowship();
```

## Update tests

Change route tests from:

```php
Fellowship::routes();
```

to:

```php
Route::fellowship();
```

Change test names from `facade_*` to `route_macro_*` to match the current API.

## Update install command output

The install command should print:

```php
use Illuminate\Support\Facades\Route;

Route::fellowship();
```

instead of the old facade snippet.
