<?php

namespace PixelError\Friendships\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a friendship relationship between two models.
 */
class Friendship extends Model
{
    /** @var list<string> The attributes that are mass assignable. */
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'pair_key',
        'status',
        'accepted_at',
        'expires_at',
    ];

    /** @var array<string, string> The attributes that should be cast. */
    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the table name for the friendship model.
     *
     * @return string Returns the table name.
     */
    public function getTable(): string
    {
        return config('friendships.tables.friendships', 'friendships');
    }

    /**
     * Get the model that sent the friendship request.
     *
     * @return BelongsTo Returns the sender relationship.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'sender_id');
    }

    /**
     * Get the user who received the friend request.
     *
     * @return BelongsTo Returns the recipient relationship.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'recipient_id');
    }
}
