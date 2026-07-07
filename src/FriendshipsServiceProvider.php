<?php

namespace EloquentWorks\Fellowship;

use Illuminate\Support\ServiceProvider;
use EloquentWorks\Fellowship\Console\Commands\ExpireFriendRequestsCommand;
use EloquentWorks\Fellowship\Console\Commands\InstallFellowshipCommand;

class FellowshipServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void Returns nothing.
     */
    public function boot(): void
    {
        // Ensure we are running in the console before publishing migrations and configuration files.
        if ($this->app->runningInConsole()) {

            // Register the console command for expiring friend requests.
            $this->commands([
                ExpireFriendRequestsCommand::class,
                InstallFellowshipCommand::class,
            ]);

            // Publish the package migrations to the application's migrations directory.
            $this->publishesMigrations([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'fellowship-migrations');

            // Publish the package configuration file to the application's config directory.
            $this->publishes([
                __DIR__.'/../config/fellowship.php' => config_path('fellowship.php'),
            ], 'fellowship-config');
        }
        // Load the package routes from the specified file.
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Register any package services.
     *
     * @return void Returns nothing.
     */
    public function register(): void
    {
        // Merge the package configuration with the application's configuration.
        $this->mergeConfigFrom(__DIR__.'/../config/fellowship.php', 'fellowship');
    }
}
