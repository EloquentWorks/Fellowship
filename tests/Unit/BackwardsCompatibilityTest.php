<?php

namespace Tests\Unit;

use EloquentWorks\Fellowship\Events\FellowshipRemoved;
use EloquentWorks\Fellowship\Events\FellowshipRequestAccepted;
use EloquentWorks\Fellowship\Events\FellowshipRequestCanceled;
use EloquentWorks\Fellowship\Events\FellowshipRequestDenied;
use EloquentWorks\Fellowship\Events\FellowshipRequestExpired;
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use EloquentWorks\Fellowship\Events\FriendRequestAccepted;
use EloquentWorks\Fellowship\Events\FriendRequestCanceled;
use EloquentWorks\Fellowship\Events\FriendRequestDenied;
use EloquentWorks\Fellowship\Events\FriendRequestExpired;
use EloquentWorks\Fellowship\Events\FriendRequestSent;
use EloquentWorks\Fellowship\Events\FriendshipRemoved;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackwardsCompatibilityTest extends TestCase
{
    #[Test]
    public function old_friend_named_event_aliases_extend_the_new_fellowship_events(): void
    {
        $this->assertTrue(is_subclass_of(FriendRequestSent::class, FellowshipRequestSent::class));
        $this->assertTrue(is_subclass_of(FriendRequestAccepted::class, FellowshipRequestAccepted::class));
        $this->assertTrue(is_subclass_of(FriendRequestDenied::class, FellowshipRequestDenied::class));
        $this->assertTrue(is_subclass_of(FriendRequestCanceled::class, FellowshipRequestCanceled::class));
        $this->assertTrue(is_subclass_of(FriendRequestExpired::class, FellowshipRequestExpired::class));
        $this->assertTrue(is_subclass_of(FriendshipRemoved::class, FellowshipRemoved::class));
    }
}
