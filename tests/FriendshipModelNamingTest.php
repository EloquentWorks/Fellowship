<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use PixelError\Friendships\Models\FriendFriendshipGroups;
use PixelError\Friendships\Models\FriendshipGroup;

class FriendshipModelNamingTest extends TestCase
{
    #[Test]
    public function friendship_uses_the_clean_friendship_group_model_name(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $sender->befriend($recipient);
        $recipient->acceptFriendRequest($sender);
        $sender->groupFriend($recipient, 'family');

        $friendship = $sender->getFriendship($recipient);
        $group = $friendship->groups()->first();

        $this->assertInstanceOf(FriendshipGroup::class, $group);
    }

    #[Test]
    public function old_friend_friendship_groups_model_name_still_extends_the_clean_model(): void
    {
        $this->assertTrue(is_subclass_of(FriendFriendshipGroups::class, FriendshipGroup::class));
    }
}
