<?php

namespace Tests\Feature;

use EloquentWorks\Fellowship\Events\FellowshipRemoved;
use EloquentWorks\Fellowship\Events\FellowshipRequestAccepted;
use EloquentWorks\Fellowship\Events\FellowshipRequestCanceled;
use EloquentWorks\Fellowship\Events\FellowshipRequestDenied;
use EloquentWorks\Fellowship\Events\FellowshipRequestExpired;
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use EloquentWorks\Fellowship\Events\UserBlocked;
use EloquentWorks\Fellowship\Events\UserUnblocked;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FellowshipEventsTest extends TestCase
{
    #[Test]
    public function it_dispatches_request_sent_event(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient);

        Event::assertDispatched(FellowshipRequestSent::class, fn ($event): bool => $event->fellowship->sender->is($sender)
            && $event->fellowship->recipient->is($recipient));
    }

    #[Test]
    public function it_dispatches_request_accepted_event(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient);
        $recipient->acceptFellowshipRequestFrom($sender);

        Event::assertDispatched(FellowshipRequestAccepted::class);
    }

    #[Test]
    public function it_dispatches_request_denied_event(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient);
        $recipient->denyFellowshipRequestFrom($sender);

        Event::assertDispatched(FellowshipRequestDenied::class);
    }

    #[Test]
    public function it_dispatches_request_canceled_event(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient);
        $sender->cancelFellowshipRequestTo($recipient);

        Event::assertDispatched(FellowshipRequestCanceled::class);
    }

    #[Test]
    public function it_dispatches_block_and_unblock_events(): void
    {
        Event::fake();

        $blocker = createUser();
        $blocked = createUser();

        $blocker->blockUser($blocked);
        $blocker->unblockUser($blocked);

        Event::assertDispatched(UserBlocked::class);
        Event::assertDispatched(UserUnblocked::class);
    }

    #[Test]
    public function it_dispatches_removed_event(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient);
        $recipient->acceptFellowshipRequestFrom($sender);
        $sender->removeFellowship($recipient);

        Event::assertDispatched(FellowshipRemoved::class);
    }

    #[Test]
    public function it_dispatches_expired_event_when_accepting_an_expired_request(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient)->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $recipient->acceptFellowshipRequestFrom($sender);

        Event::assertDispatched(FellowshipRequestExpired::class);
    }

    #[Test]
    public function events_can_be_disabled_in_config(): void
    {
        Event::fake();
        config()->set('fellowship.dispatch_events', false);

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFellowshipRequestTo($recipient);

        Event::assertNotDispatched(FellowshipRequestSent::class);
    }
}
