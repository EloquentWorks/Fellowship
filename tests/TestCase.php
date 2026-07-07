<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PixelError\Friendships\FriendshipsServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FriendshipsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('friendships.tables.fr_pivot', 'friendships');
        $app['config']->set('friendships.tables.fr_groups_pivot', 'user_friendship_groups');
        $app['config']->set('friendships.groups', [
            'acquaintances' => 0,
            'close_friends' => 1,
            'family' => 2,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function setUpDatabase(): void
    {
        Schema::dropIfExists('user_friendship_groups');
        Schema::dropIfExists('friendships');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->morphs('sender');
            $table->morphs('recipient');
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        Schema::create('user_friendship_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('friendship_id');
            $table->morphs('friend');
            $table->unsignedInteger('group_id');

            $table->foreign('friendship_id')
                ->references('id')
                ->on('friendships')
                ->cascadeOnDelete();

            $table->unique(
                ['friendship_id', 'friend_id', 'friend_type', 'group_id'],
                'friendship_group_unique'
            );
        });
    }
}
