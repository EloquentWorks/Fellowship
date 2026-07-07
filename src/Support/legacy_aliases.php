<?php

use PixelError\Friendships\FriendshipsServiceProvider;
use PixelError\Friendships\Models\FriendFriendshipGroups;
use PixelError\Friendships\Models\Friendship;
use PixelError\Friendships\Models\FriendshipGroup;
use PixelError\Friendships\Status;
use PixelError\Friendships\Traits\Friendable;

/*
|--------------------------------------------------------------------------
| Legacy Namespace Aliases
|--------------------------------------------------------------------------
|
| Older applications may still reference the original Hootlex namespace.
| These aliases let those projects move to the maintained PixelError package
| without immediately rewriting every import.
|
| New applications should use the PixelError\Friendships namespace directly.
|
*/

if (! class_exists('Hootlex\\Friendships\\FriendshipsServiceProvider', false)) {
    class_alias(
        FriendshipsServiceProvider::class,
        'Hootlex\\Friendships\\FriendshipsServiceProvider'
    );
}

if (! class_exists('Hootlex\\Friendships\\Status', false)) {
    class_alias(
        Status::class,
        'Hootlex\\Friendships\\Status'
    );
}

if (! class_exists('Hootlex\\Friendships\\Models\\Friendship', false)) {
    class_alias(
        Friendship::class,
        'Hootlex\\Friendships\\Models\\Friendship'
    );
}

if (! class_exists('Hootlex\\Friendships\\Models\\FriendshipGroup', false)) {
    class_alias(
        FriendshipGroup::class,
        'Hootlex\\Friendships\\Models\\FriendshipGroup'
    );
}

if (! class_exists('Hootlex\\Friendships\\Models\\FriendFriendshipGroups', false)) {
    class_alias(
        FriendFriendshipGroups::class,
        'Hootlex\\Friendships\\Models\\FriendFriendshipGroups'
    );
}

if (! trait_exists('Hootlex\\Friendships\\Traits\\Friendable', false)) {
    class_alias(
        Friendable::class,
        'Hootlex\\Friendships\\Traits\\Friendable'
    );
}
