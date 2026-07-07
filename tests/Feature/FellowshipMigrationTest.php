<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FellowshipMigrationTest extends TestCase
{
    #[Test]
    public function migration_creates_the_friendships_table_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('friendships'));

        foreach ([
            'id',
            'sender_id',
            'recipient_id',
            'pair_key',
            'status',
            'message',
            'is_favorite',
            'muted_at',
            'accepted_at',
            'expires_at',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('friendships', $column), "Missing column [{$column}].");
        }
    }

    #[Test]
    public function migration_can_be_reversed(): void
    {
        $migration = require __DIR__.'/../../database/migrations/2026_07_07_000000_create_friendships_table.php';

        $migration->down();

        $this->assertFalse(Schema::hasTable('friendships'));

        $migration->up();

        $this->assertTrue(Schema::hasTable('friendships'));
    }
}
