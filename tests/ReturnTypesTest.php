<?php

namespace Tests;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use PixelError\Friendships\Models\Friendship;

class ReturnTypesTest extends TestCase
{
    #[Test]
    public function core_friendship_methods_return_expected_types(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $this->assertNull($sender->getFriendship($recipient));

        $friendship = $sender->befriend($recipient);

        $this->assertInstanceOf(Friendship::class, $friendship);
        $this->assertFalse($sender->befriend($recipient));
        $this->assertSame(1, $recipient->acceptFriendRequest($sender));
        $this->assertTrue($sender->isFriendWith($recipient));
        $this->assertSame(1, $sender->unfriend($recipient));
    }

    #[Test]
    public function friend_list_methods_return_collections_or_paginators(): void
    {
        $sender = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->befriend($recipient);
            $recipient->acceptFriendRequest($sender);
        }

        $this->assertInstanceOf(EloquentCollection::class, $sender->getFriends());
        $this->assertInstanceOf(LengthAwarePaginator::class, $sender->getFriends(2));
        $this->assertInstanceOf(EloquentCollection::class, $sender->getAcceptedFriendships());
        $this->assertSame(3, $sender->getFriendsCount());
    }
}
