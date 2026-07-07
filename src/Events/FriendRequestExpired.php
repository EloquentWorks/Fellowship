<?php

namespace EloquentWorks\Fellowship\Events;

use EloquentWorks\Fellowship\Models\Fellowship;

/**
 * Event fired when a fellowship request expires.
 */
class FellowshipRequestExpired
{
    /**
     * Create a new event instance.
     *
     * @param  Fellowship  $fellowship  The fellowship model related to the event.
     * @return void Returns nothing.
     */
    public function __construct(
        public Fellowship $fellowship
    ) {
        //
    }
}
