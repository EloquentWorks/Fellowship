<?php

namespace PixelError\Friendships\Models;

use Illuminate\Database\Eloquent\Model;

class FriendshipGroup extends Model
{
    protected $fillable = [
        'friendship_id',
        'group_id',
        'friend_id',
        'friend_type',
    ];

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        $this->table = config('friendships.tables.fr_groups_pivot');
        parent::__construct($attributes);
    }
}
