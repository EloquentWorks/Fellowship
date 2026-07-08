<?php

namespace Tests\Feature;

use EloquentWorks\Fellowship\Status;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FellowshipControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::fellowship([
            'middleware' => ['web', 'auth'],
        ]);
    }

    #[Test]
    public function authenticated_user_can_send_a_request_through_the_web_route(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $this->actingAs($sender)
            ->from('/people')
            ->post(route('fellowship.requests.store', $recipient))
            ->assertRedirect('/people')
            ->assertSessionHas('fellowship.status', 'request_sent');

        $this->assertSame(Status::PENDING, $sender->friendshipStatusWith($recipient));
    }

    #[Test]
    public function unauthenticated_users_are_redirected_by_default_routes(): void
    {
        $recipient = createUser();

        $this->post(route('fellowship.requests.store', $recipient))
            ->assertRedirect();
    }

    #[Test]
    public function recipient_can_accept_a_request_through_the_web_route(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->actingAs($recipient)
            ->from('/requests')
            ->post(route('fellowship.requests.accept', $sender))
            ->assertRedirect('/requests')
            ->assertSessionHas('fellowship.status', 'request_accepted');

        $this->assertTrue($recipient->isFriendsWith($sender));
    }

    #[Test]
    public function recipient_can_deny_a_request_through_the_web_route(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->actingAs($recipient)
            ->from('/requests')
            ->post(route('fellowship.requests.deny', $sender))
            ->assertRedirect('/requests')
            ->assertSessionHas('fellowship.status', 'request_denied');

        $this->assertSame(Status::DENIED, $recipient->friendshipStatusWith($sender));
    }

    #[Test]
    public function sender_can_cancel_a_request_through_the_web_route(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->actingAs($sender)
            ->from('/requests')
            ->delete(route('fellowship.requests.cancel', $recipient))
            ->assertRedirect('/requests')
            ->assertSessionHas('fellowship.status', 'request_canceled');

        $this->assertSame(Status::CANCELED, $sender->friendshipStatusWith($recipient));
    }

    #[Test]
    public function user_can_remove_a_friend_through_the_web_route(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);
        $recipient->acceptFriendRequestFrom($sender);

        $this->actingAs($sender)
            ->from('/friends')
            ->delete(route('fellowship.friends.remove', $recipient))
            ->assertRedirect('/friends')
            ->assertSessionHas('fellowship.status', 'friend_removed');

        $this->assertFalse($sender->isFriendsWith($recipient));
    }

    #[Test]
    public function user_can_block_and_unblock_through_the_web_routes(): void
    {
        $blocker = createUser();
        $blocked = createUser();

        $this->actingAs($blocker)
            ->from('/profile')
            ->post(route('fellowship.blocks.store', $blocked))
            ->assertRedirect('/profile')
            ->assertSessionHas('fellowship.status', 'user_blocked');

        $this->assertTrue($blocker->hasBlocked($blocked));

        $this->actingAs($blocker)
            ->from('/profile')
            ->delete(route('fellowship.blocks.destroy', $blocked))
            ->assertRedirect('/profile')
            ->assertSessionHas('fellowship.status', 'user_unblocked');

        $this->assertFalse($blocker->hasBlocked($blocked));
    }
}
