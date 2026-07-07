<?php

namespace EloquentWorks\Fellowship;

use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;
use Illuminate\Routing\Router;

class Fellowship
{
    /**
     * Create a new Fellowship instance.
     *
     * @param  Router  $router  The router instance for defining routes.
     * @return void Returns nothing.
     */
    public function __construct(protected Router $router)
    {
        //
    }

    /**
     * Register the fellowship routes.
     *
     * @param  array  $options  Optional configuration options for the routes.
     */
    public function routes(array $options = []): void
    {
        // Get the configuration for fellowship routes, falling back to an empty array if not set.
        $config = config('fellowship.routes', []);
        $config = is_array($config) ? $config : [];

        // Determine the middleware, prefix, name, and controller for the routes, using the provided
        // options or falling back to the configuration values or defaults.
        $middleware = $options['middleware'] ?? $config['middleware'] ?? ['web', 'auth'];
        $prefix = $options['prefix'] ?? $config['prefix'] ?? 'fellowship';
        $name = $options['name'] ?? $config['name'] ?? 'fellowship.';
        $controller = $options['controller'] ?? FellowshipController::class;

        // Use the router to define a group of routes with the specified middleware, prefix, name, and controller.
        $router = $this->router;

        // Define the fellowship routes within the group.
        $router->middleware($middleware)
            ->prefix($prefix)
            ->name($name)
            ->controller($controller)
            ->group(function () use ($router): void {
                $router->post('/{user}/request', 'send')->name('requests.store');
                $router->post('/{user}/accept', 'accept')->name('requests.accept');
                $router->post('/{user}/deny', 'deny')->name('requests.deny');
                $router->delete('/{user}/cancel', 'cancel')->name('requests.cancel');
                $router->delete('/{user}/remove', 'remove')->name('friends.remove');
                $router->post('/{user}/block', 'block')->name('blocks.store');
                $router->delete('/{user}/unblock', 'unblock')->name('blocks.destroy');
            });
    }
}
