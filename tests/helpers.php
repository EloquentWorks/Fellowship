<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\Support\User;

function createUser(array $attributes = [], int $count = 1): User|Collection
{
    $users = Collection::times($count, function () use ($attributes): User {
        $uuid = (string) Str::uuid();

        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => "user-{$uuid}@example.test",
            'password' => bcrypt('password'),
        ], $attributes));
    });

    return $count === 1 ? $users->first() : $users;
}
