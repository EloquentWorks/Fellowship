<?php

namespace PixelError\Friendships\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores a user's personal group assignment for a friendship.
 */
class FriendshipGroup extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'friendship_id',
        'group_id',
        'friend_id',
        'friend_type',
    ];

    /**
     * Friendship groups are pivot-style records and do not use timestamps.
     */
    public $timestamps = false;

    /**
     * Create a new friendship group model instance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('friendships.tables.fr_groups_pivot');

        parent::__construct($attributes);
    }
}
