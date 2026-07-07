<?php

namespace Tests\Unit;

use EloquentWorks\Fellowship\Models\Fellowship;
use EloquentWorks\Fellowship\Status;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FellowshipModelTest extends TestCase
{
    #[Test]
    public function it_uses_the_configured_table_name(): void
    {
        config()->set('fellowship.tables.friendships', 'custom_friendships');

        $this->assertSame('custom_friendships', (new Fellowship)->getTable());
    }

    #[Test]
    public function it_casts_date_and_boolean_columns(): void
    {
        $sender = createUser();
        $recipient = createUser();

        $fellowship = Fellowship::query()->create([
            'sender_id' => $sender->getKey(),
            'recipient_id' => $recipient->getKey(),
            'pair_key' => '1:2',
            'status' => Status::ACCEPTED,
            'accepted_at' => now(),
            'expires_at' => now()->addDay(),
            'muted_at' => now(),
            'is_favorite' => 1,
        ]);

        $this->assertTrue($fellowship->accepted_at->isToday());
        $this->assertTrue($fellowship->expires_at->isFuture());
        $this->assertTrue($fellowship->muted_at->isToday());
        $this->assertTrue($fellowship->is_favorite);
    }

    #[Test]
    public function it_belongs_to_a_sender_and_recipient(): void
    {
        $sender = createUser(['name' => 'Sender']);
        $recipient = createUser(['name' => 'Recipient']);

        $fellowship = Fellowship::query()->create([
            'sender_id' => $sender->getKey(),
            'recipient_id' => $recipient->getKey(),
            'pair_key' => '1:2',
            'status' => Status::PENDING,
        ]);

        $this->assertTrue($sender->is($fellowship->sender));
        $this->assertTrue($recipient->is($fellowship->recipient));
    }
}
