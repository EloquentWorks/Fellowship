<?php

namespace Tests\Feature;

use EloquentWorks\Fellowship\Models\Fellowship;
use EloquentWorks\Fellowship\Status;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HasFriendshipsTest extends TestCase
{
    #[Test]
    public function a_user_can_send_a_friend_request(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $fellowship = $sender->sendFriendRequestTo($recipient);

        $this->assertInstanceOf(Fellowship::class, $fellowship);
        $this->assertTrue($sender->hasPendingFriendRequestWith($recipient));
        $this->assertTrue($recipient->hasPendingFriendRequestWith($sender));
        $this->assertSame(Status::PENDING, $sender->friendshipStatusWith($recipient));
        $this->assertSame($sender->getKey().':'.$recipient->getKey(), $fellowship->pair_key);
        $this->assertTrue($fellowship->expires_at->isFuture());
        $this->assertCount(1, $sender->outgoingFriendRequests());
        $this->assertCount(1, $recipient->incomingFriendRequests());
    }

    #[Test]
    public function duplicate_pending_requests_are_rejected(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('A friend request already exists between these users.');

        $sender->sendFriendRequestTo($recipient);
    }

    #[Test]
    public function recipient_can_accept_a_friend_request(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->assertTrue($recipient->acceptFriendRequestFrom($sender));
        $this->assertTrue($sender->isFriendsWith($recipient));
        $this->assertTrue($recipient->isFriendsWith($sender));
        $this->assertSame(Status::ACCEPTED, $sender->friendshipStatusWith($recipient));
        $this->assertSame(1, $sender->friendsCount());
        $this->assertSame(1, $recipient->friendsCount());
        $this->assertTrue($recipient->friendshipWith($sender)->accepted_at->isToday());
        $this->assertNull($recipient->friendshipWith($sender)->expires_at);
    }

    #[Test]
    public function sender_cannot_accept_their_own_outgoing_request(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->assertFalse($sender->acceptFriendRequestFrom($recipient));
        $this->assertFalse($sender->isFriendsWith($recipient));
    }

    #[Test]
    public function recipient_can_deny_a_friend_request(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->assertTrue($recipient->denyFriendRequestFrom($sender));
        $this->assertSame(Status::DENIED, $sender->friendshipStatusWith($recipient));
        $this->assertCount(0, $recipient->incomingFriendRequests());
    }

    #[Test]
    public function sender_can_cancel_an_outgoing_request(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);

        $this->assertTrue($sender->cancelFriendRequestTo($recipient));
        $this->assertSame(Status::CANCELED, $sender->friendshipStatusWith($recipient));
        $this->assertCount(0, $sender->outgoingFriendRequests());
    }

    #[Test]
    public function users_can_resend_after_cooldown_is_disabled(): void
    {
        config()->set('fellowship.request_cooldown_days', 0);

        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);
        $recipient->denyFriendRequestFrom($sender);

        $resent = $sender->sendFriendRequestTo($recipient);

        $this->assertSame(Status::PENDING, $resent->status);
        $this->assertSame($sender->getKey(), $resent->sender_id);
        $this->assertSame($recipient->getKey(), $resent->recipient_id);
    }

    #[Test]
    public function users_cannot_resend_inside_the_cooldown_window(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);
        $recipient->denyFriendRequestFrom($sender);

        $this->assertFalse($sender->canSendFriendRequestTo($recipient));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must wait before sending another friend request to this user.');

        $sender->sendFriendRequestTo($recipient);
    }

    #[Test]
    public function users_can_resend_after_the_cooldown_window(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);
        $recipient->denyFriendRequestFrom($sender);

        $fellowship = $sender->friendshipWith($recipient);

        DB::table('friendships')
            ->where('id', $fellowship->getKey())
            ->update(['updated_at' => now()->subDays(8)]);

        $this->assertTrue($sender->canSendFriendRequestTo($recipient));
        $this->assertSame(Status::PENDING, $sender->sendFriendRequestTo($recipient)->status);
    }

    #[Test]
    public function accepted_users_can_remove_each_other_as_friends(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient);
        $recipient->acceptFriendRequestFrom($sender);

        $this->assertTrue($sender->removeFriend($recipient));
        $this->assertFalse($sender->isFriendsWith($recipient));
        $this->assertNull($sender->friendshipWith($recipient));
    }

    #[Test]
    public function blocking_prevents_friend_requests_until_unblocked(): void
    {
        $blocker = createUser();
        $blocked = createUser();

        $fellowship = $blocker->blockUser($blocked);

        $this->assertSame(Status::BLOCKED, $fellowship->status);
        $this->assertTrue($blocker->hasBlocked($blocked));
        $this->assertTrue($blocked->isBlockedBy($blocker));
        $this->assertFalse($blocked->hasBlocked($blocker));
        $this->assertFalse($blocker->canSendFriendRequestTo($blocked));

        $this->expectException(LogicException::class);
        $blocked->sendFriendRequestTo($blocker);
    }

    #[Test]
    public function only_the_blocker_can_unblock_the_blocked_user(): void
    {
        $blocker = createUser();
        $blocked = createUser();

        $blocker->blockUser($blocked);

        $this->assertFalse($blocked->unblockUser($blocker));
        $this->assertTrue($blocker->hasBlocked($blocked));

        $this->assertTrue($blocker->unblockUser($blocked));
        $this->assertFalse($blocker->hasBlocked($blocked));
    }

    #[Test]
    public function it_returns_friends_blocked_users_and_users_who_blocked_the_model(): void
    {
        $user = createUser();
        $friend = createUser();
        $blocked = createUser();
        $blockedBy = createUser();

        $user->sendFriendRequestTo($friend);
        $friend->acceptFriendRequestFrom($user);
        $user->blockUser($blocked);
        $blockedBy->blockUser($user);

        $this->assertInstanceOf(EloquentCollection::class, $user->friends());
        $this->assertTrue($user->friends()->first()->is($friend));
        $this->assertTrue($user->blockedUsers()->first()->is($blocked));
        $this->assertTrue($user->blockedByUsers()->first()->is($blockedBy));
    }

    #[Test]
    public function it_counts_mutual_friends(): void
    {
        $user = createUser();
        $otherUser = createUser();
        $mutual = createUser();
        $notMutual = createUser();

        $user->sendFriendRequestTo($mutual);
        $mutual->acceptFriendRequestFrom($user);

        $otherUser->sendFriendRequestTo($mutual);
        $mutual->acceptFriendRequestFrom($otherUser);

        $user->sendFriendRequestTo($notMutual);
        $notMutual->acceptFriendRequestFrom($user);

        $this->assertSame(1, $user->mutualFriendsCountWith($otherUser));
        $this->assertTrue($user->mutualFriendsWith($otherUser)->first()->is($mutual));
    }

    #[Test]
    public function expired_pending_requests_are_not_counted_as_pending_and_cannot_be_accepted(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->sendFriendRequestTo($recipient)->forceFill([
            'expires_at' => now()->subMinute(),
        ])->save();

        $this->assertFalse($sender->hasPendingFriendRequestWith($recipient));
        $this->assertFalse($recipient->acceptFriendRequestFrom($sender));
        $this->assertSame(Status::EXPIRED, $sender->friendshipStatusWith($recipient));
    }

    #[Test]
    public function friend_requests_can_be_configured_to_never_expire(): void
    {
        config()->set('fellowship.expires_after_days', null);

        $sender = createUser();
        $recipient = createUser();

        $fellowship = $sender->sendFriendRequestTo($recipient);

        $this->assertNull($fellowship->expires_at);
    }

    #[Test]
    public function self_friendship_actions_throw_a_logic_exception(): void
    {
        $user = createUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot send a friend request to yourself.');

        $user->sendFriendRequestTo($user);
    }
}
