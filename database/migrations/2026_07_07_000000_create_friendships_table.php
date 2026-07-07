<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
        // Create the friendships table with the specified schema.
        Schema::create(config('friendships.tables.friendships', 'friendships'), function (Blueprint $table) use ($usersTable): void {
            // Auto-incrementing primary key for the friendships table.
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

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the friendships table if it exists.
        Schema::dropIfExists(config('friendships.tables.friendships', 'friendships'));
    }
};
