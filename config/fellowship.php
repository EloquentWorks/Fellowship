<?php

use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;
use EloquentWorks\Fellowship\Models\Fellowship;

return [

    /*
    |--------------------------------------------------------------------------
    | Fellowship Models
    |--------------------------------------------------------------------------
    |
    | These are the model classes used by the package.
    |
    | You may replace these with your own custom models if you need to extend
    | the default package behavior.
    |
    | The user model used when resolving route parameters and model
    | relationships. Leave this as null to fall back to Laravel's
    | configured auth user model.
    |
    */

    'models' => [
        'fellowship' => Fellowship::class,
        'user' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | These table names are used by the package when storing friendships.
    |
    | You may change these names if your application uses custom table names.
    | If you do change them, make sure your migrations use the same names.
    |
    */

    'tables' => [
        'users' => 'users',
        'friendships' => 'friendships',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Defaults
    |--------------------------------------------------------------------------
    |
    | Fellowship does not load web routes automatically. Register them inside
    | your application's routes/web.php file with:
    |
    | Route::fellowship();
    |
    | You may publish a copyable route snippet with:
    |
    | php artisan vendor:publish --tag=fellowship-routes
    |
    */

    'routes' => [
        'prefix' => 'fellowship',
        'middleware' => ['web', 'auth'],
        'name' => 'fellowship.',
        'controller' => FellowshipController::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Friend Request Expiration
    |--------------------------------------------------------------------------
    |
    | This controls how long pending friend requests remain valid.
    |
    | Set this value to null if friend requests should never expire.
    |
    */

    'expires_after_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Friend Request Cooldown
    |--------------------------------------------------------------------------
    |
    | This controls how long a user must wait before sending another friend
    | request after a request was denied, canceled, or expired.
    |
    | Set this value to null or 0 to disable friend request cooldowns.
    |
    */

    'request_cooldown_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Fellowship Events
    |--------------------------------------------------------------------------
    |
    | This controls whether the package dispatches fellowship lifecycle events.
    |
    | Events are useful for notifications, activity feeds, logging, and other
    | application-specific side effects.
    |
    */

    'dispatch_events' => true,

];
