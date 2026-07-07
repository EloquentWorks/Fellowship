<?php

namespace PixelError\Friendships\Events;

use PixelError\Friendships\Models\Friendship;

/**
 * Event fired when a friend request is sent.
 */
class FriendRequestSent
{
    /**
     * Create a new event instance.
     *
     * @param  Friendship  $friendship  The friendship model related to the event.
     * @return void Returns nothing.
     */
    public function __construct(
        public Friendship $friendship
    ) {
        //
    }
}
