<?php

namespace Tests;

use EloquentWorks\Fellowship\FellowshipServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tests\Support\User;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            FellowshipServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Fellowship' => \EloquentWorks\Fellowship\Facades\Fellowship::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);

        $app['config']->set('fellowship.tables.users', 'users');
        $app['config']->set('fellowship.tables.friendships', 'friendships');
        $app['config']->set('fellowship.expires_after_days', 30);
        $app['config']->set('fellowship.request_cooldown_days', 7);
        $app['config']->set('fellowship.dispatch_events', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        Route::get('/login', fn (): string => 'login')->name('login');
    }

    protected function setUpDatabase(): void
    {
        Schema::dropIfExists('friendships');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $migration = require __DIR__.'/../database/migrations/2026_07_07_000000_create_friendships_table.php';
        $migration->up();
    }
}
