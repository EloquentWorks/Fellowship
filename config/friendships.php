<?php

use PixelError\Friendships\Models\Friendship;

return [

    /*
    |--------------------------------------------------------------------------
    | Friendship Models
    |--------------------------------------------------------------------------
    |
    | These are the model classes used by the package.
    |
    | You may replace these with your own custom models if you need to extend
    | the default package behavior.
    |
    */

    'models' => [
        'friendship' => Friendship::class,
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
        'fr_pivot' => 'friendships',
        'friendships' => 'user_friendships',
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
    | Friendship Events
    |--------------------------------------------------------------------------
    |
    | This controls whether the package dispatches friendship lifecycle events.
    |
    | Events are useful for notifications, activity feeds, logging, and other
    | application-specific side effects.
    |
    */

    'dispatch_events' => true,

];
