<?php

namespace Tests\Unit;

use EloquentWorks\Fellowship\Events\FellowshipRemoved;
use EloquentWorks\Fellowship\Events\FellowshipRequestAccepted;
use EloquentWorks\Fellowship\Events\FellowshipRequestCanceled;
use EloquentWorks\Fellowship\Events\FellowshipRequestDenied;
use EloquentWorks\Fellowship\Events\FellowshipRequestExpired;
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackwardsCompatibilityTest extends TestCase
{
    #[Test]
    public function old_friend_named_event_aliases_extend_the_new_fellowship_events(): void
    {
        $this->assertTrue(is_subclass_of(\EloquentWorks\Fellowship\Events\FriendRequestSent::class, FellowshipRequestSent::class));
        $this->assertTrue(is_subclass_of(\EloquentWorks\Fellowship\Events\FriendRequestAccepted::class, FellowshipRequestAccepted::class));
        $this->assertTrue(is_subclass_of(\EloquentWorks\Fellowship\Events\FriendRequestDenied::class, FellowshipRequestDenied::class));
        $this->assertTrue(is_subclass_of(\EloquentWorks\Fellowship\Events\FriendRequestCanceled::class, FellowshipRequestCanceled::class));
        $this->assertTrue(is_subclass_of(\EloquentWorks\Fellowship\Events\FriendRequestExpired::class, FellowshipRequestExpired::class));
        $this->assertTrue(is_subclass_of(\EloquentWorks\Fellowship\Events\FriendshipRemoved::class, FellowshipRemoved::class));
    }
}
