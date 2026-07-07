<?php

namespace EloquentWorks\Fellowship;

use EloquentWorks\Fellowship\Console\Commands\ExpireFellowshipRequestsCommand;
use EloquentWorks\Fellowship\Console\Commands\InstallFellowshipCommand;
use EloquentWorks\Fellowship\Facades\Fellowship as FellowshipManager;
use Illuminate\Support\ServiceProvider;

class FellowshipServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void Returns nothing.
     */
    public function register(): void
    {
        // Merge the package's configuration file with the application's configuration
        $this->mergeConfigFrom(__DIR__.'/../config/fellowship.php', 'fellowship');

        // Register the Fellowship Facade class as a singleton in the service container
        $this->app->singleton('fellowship', function ($app): FellowshipManager {
            return new FellowshipManager($app['router']);
        });

        // Register the Fellowship class as an alias for the 'fellowship' singleton in the service container
        $this->app->alias('fellowship', FellowshipManager::class);
    }

    /**
     * Bootstrap any package services.
     *
     * @return void Returns nothing.
     */
    public function boot(): void
    {
        // Ensure that the application is running in the console before registering commands and publishing resources.
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Register the package's console commands.
        $this->commands([
            ExpireFellowshipRequestsCommand::class,
            InstallFellowshipCommand::class,
        ]);

        // Publish the package's configuration file, migrations, and routes.
        $this->publishes([
            __DIR__.'/../config/fellowship.php' => config_path('fellowship.php'),
        ], 'fellowship-config');

        // Publish the package's migration files to the application's database/migrations directory.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'fellowship-migrations');

        // Publish the package's route file to the application's routes directory.
        $this->publishes([
            __DIR__.'/../routes/web.php' => base_path('routes/fellowship.php'),
        ], 'fellowship-routes');
    }
}
