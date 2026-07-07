<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PixelError\Friendships\StatusOld;

/**
 * Migration class for creating the friendships table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void Returns nothing.
     */
    public function up(): void
    {
        // Get the names of the old and new friendship tables from the configuration, defaulting to 'friendships' and 'user_friendships' respectively.
        $oldTable = config('friendships.tables.fr_pivot', 'friendships');
        $newTable = config('friendships.tables.friendships', 'user_friendships');

        // Check if the old friendship table and new friendship table have the same name.
        $usersTable = config('friendships.tables.users', 'users');

        // Ensure that the old friendship table and new friendship table do not have the same name to avoid conflicts.
        if ($oldTable === $newTable) {
            throw new RuntimeException('The old friendship table and new friendship table cannot use the same name.');
        }

        // Get the name of the users table from the configuration, defaulting to 'users'.
        if (! Schema::hasTable($newTable)) {
            Schema::create($newTable, function (Blueprint $table) use ($usersTable): void {
                $table->id();

                // The user who sent the friend request.
                $table->foreignId('sender_id')
                    ->constrained($usersTable)
                    ->cascadeOnDelete();

                // The user who received the friend request.
                $table->foreignId('recipient_id')
                    ->constrained($usersTable)
                    ->cascadeOnDelete();

                // Create a unique pair key for the friendship relationship.
                $table->string('pair_key')->unique();

                // The status of the friendship request, defaulting to 'pending'.
                $table->string('status')->default(Status::PENDING);

                // Optional message attached to the friend request.
                $table->text('message')->nullable();

                // Mark the friendship as a favorite friend.
                $table->boolean('is_favorite')->default(false);

                // When the friendship was muted by the current sender side.
                $table->timestamp('muted_at')->nullable();

                // When a pending friend request is accepted.
                $table->timestamp('accepted_at')->nullable();

                // When a friendship expires, if applicable.
                $table->timestamp('expires_at')->nullable();

                // Add created_at and updated_at timestamps.
                $table->timestamps();

                // Add indexes for efficient querying on sender_id, recipient_id, status, expires_at, and the combination of sender_id and recipient_id.
                $table->index('sender_id');
                $table->index('recipient_id');

                // Add an index for the status column to optimize queries that filter by friendship status.
                $table->index('status');

                // Add an index for the expires_at column to optimize queries that filter by expiration date.
                $table->index('expires_at');

                // Add an index for favorite friendships.
                $table->index('is_favorite');

                // Add an index for muted friendships.
                $table->index('muted_at');

                // Add a composite index for sender_id and recipient_id to optimize queries involving both columns.
                $table->index(['sender_id', 'recipient_id']);
            });
        }

        // Migrate data from the old friendship table to the new friendship table in chunks to avoid memory issues.
        if (Schema::hasTable($oldTable)) {
            DB::table($oldTable)->orderBy('id')->chunkById(100, function ($friendships) use ($newTable, $usersTable): void {
                foreach ($friendships as $friendship) {
                    // Check if both the sender and recipient users exist in the users table before migrating the friendship record.
                    if (! $this->userExists($usersTable, $friendship->sender_id)) {
                        continue;
                    }
                    if (! $this->userExists($usersTable, $friendship->recipient_id)) {
                        continue;
                    }

                    // Map the old friendship status to the new friendship status using a helper method.
                    $status = $this->mapOldStatusToNewStatus((int) $friendship->status);

                    // Create a payload for the new friendship record, including sender_id, recipient_id, pair_key, status, accepted_at, expires_at, created_at, and updated_at.
                    $payload = [
                        'sender_id' => $friendship->sender_id,
                        'recipient_id' => $friendship->recipient_id,
                        'pair_key' => $this->makePairKey($friendship->sender_id, $friendship->recipient_id),
                        'status' => $status,
                        'message' => null,
                        'is_favorite' => false,
                        'muted_at' => null,
                        'accepted_at' => $status === 'accepted'
                            ? ($friendship->updated_at ?? $friendship->created_at ?? now())
                            : null,
                        'expires_at' => null,
                        'created_at' => $friendship->created_at ?? now(),
                        'updated_at' => $friendship->updated_at ?? now(),
                    ];

                    // Check if a friendship record with the same pair_key already exists in the new friendship table.
                    $existing = DB::table($newTable)
                        ->where('pair_key', $payload['pair_key'])
                        ->first();

                    // If an existing record is found, compare the status priority and update the record if the new status has a higher priority.
                    if ($existing) {
                        if ($this->statusPriority($payload['status']) > $this->statusPriority($existing->status)) {
                            DB::table($newTable)
                                ->where('id', $existing->id)
                                ->update($payload);
                        }

                        // Skip inserting the new record since an existing record with the same pair_key already exists.
                        continue;
                    }

                    // Insert the new friendship record into the new friendship table.
                    DB::table($newTable)->insert($payload);
                }
            });

            // Drop the old friendship table after migrating the data.
            Schema::dropIfExists($oldTable);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('friendships.tables.friendships', 'friendships'));
    }
};
