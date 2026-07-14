<?php

namespace EloquentWorks\Fellowship;

use EloquentWorks\Fellowship\Console\Commands\ExpireFellowshipRequestsCommand;
use EloquentWorks\Fellowship\Console\Commands\InstallFellowshipCommand;
use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
    }

    /**
     * Bootstrap any package services.
     *
     * @return void Returns nothing.
     */
    public function boot(): void
    {
        // Register the Route::fellowship() macro to define fellowship-related routes.
        $this->registerRoutesMacro();

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

    /**
     * Register the Route::fellowship() macro.
     *
     * @return void Returns nothing.
     */
    protected function registerRoutesMacro(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        // Register the Route::fellowship() macro to define fellowship-related routes with customizable options.
        $router->macro('fellowship', function (array $options = []) use ($router): void {
            $config = config('fellowship.routes', []);
            $config = is_array($config) ? $config : [];

            // Determine the middleware, prefix, name, and controller for the fellowship routes based on the provided options or configuration defaults.
            $middleware = $options['middleware'] ?? $config['middleware'] ?? ['web', 'auth'];
            $prefix = $options['prefix'] ?? $config['prefix'] ?? 'fellowship';
            $name = $options['name'] ?? $config['name'] ?? 'fellowship.';
            $controller = $options['controller'] ?? $config['controller'] ?? FellowshipController::class;

            // Validate the middleware and controller values to ensure they are of the expected types.
            if (! is_array($middleware) && ! is_string($middleware)) {
                $middleware = ['web', 'auth'];
            }

            //  Validate the controller value to ensure it is a string representing a valid controller class.
            if (! is_string($controller)) {
                $controller = FellowshipController::class;
            }

            // Trim any leading or trailing slashes from the prefix and ensure the name ends with a dot for route naming consistency.
            $prefix = trim((string) $prefix, '/');
            $name = Str::finish((string) $name, '.');

            // Define the route for sending fellowship requests.
            $router->post($prefix.'/{user}/request', [$controller, 'send'])
                ->middleware($middleware)
                ->name($name.'requests.store');

            // Define routes for accepting, denying, canceling, and removing fellowship requests, as well as blocking and unblocking users.
            $router->post($prefix.'/{user}/accept', [$controller, 'accept'])
                ->middleware($middleware)
                ->name($name.'requests.accept');

            // Define the route for denying fellowship requests.
            $router->post($prefix.'/{user}/deny', [$controller, 'deny'])
                ->middleware($middleware)
                ->name($name.'requests.deny');

            // Define the route for canceling fellowship requests.
            $router->delete($prefix.'/{user}/cancel', [$controller, 'cancel'])
                ->middleware($middleware)
                ->name($name.'requests.cancel');

            // Define the route for removing fellowships.
            $router->delete($prefix.'/{user}/remove', [$controller, 'remove'])
                ->middleware($middleware)
                ->name($name.'blocks.remove');

            // Define the route for blocking users.
            $router->post($prefix.'/{user}/block', [$controller, 'block'])
                ->middleware($middleware)
                ->name($name.'blocks.store');

            // Define the route for unblocking users.
            $router->delete($prefix.'/{user}/unblock', [$controller, 'unblock'])
                ->middleware($middleware)
                ->name($name.'blocks.destroy');

            // Refresh the route name and action lookups to ensure that the newly defined routes are properly registered and accessible.
            $router->getRoutes()->refreshNameLookups();
            $router->getRoutes()->refreshActionLookups();
        });
    }
}
