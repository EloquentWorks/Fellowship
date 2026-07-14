<?php

namespace Tests\Feature;

use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteRegistrationTest extends TestCase
{
    #[Test]
    public function route_macro_registers_default_fellowship_routes(): void
    {
        Route::fellowship(['middleware' => ['web', 'auth']]);

        $this->assertRoute('fellowship.requests.store', 'POST', 'fellowship/{user}/request');
        $this->assertRoute('fellowship.requests.accept', 'POST', 'fellowship/{user}/accept');
        $this->assertRoute('fellowship.requests.deny', 'POST', 'fellowship/{user}/deny');
        $this->assertRoute('fellowship.requests.cancel', 'DELETE', 'fellowship/{user}/cancel');
        $this->assertRoute('fellowship.blocks.remove', 'DELETE', 'fellowship/{user}/remove');
        $this->assertRoute('fellowship.blocks.store', 'POST', 'fellowship/{user}/block');
        $this->assertRoute('fellowship.blocks.destroy', 'DELETE', 'fellowship/{user}/unblock');
    }

    #[Test]
    public function route_macro_accepts_custom_route_options(): void
    {
        Route::fellowship([
            'prefix' => 'connections',
            'name' => 'connections.',
            'middleware' => ['web'],
        ]);

        $route = Route::getRoutes()->getByName('connections.requests.store');

        $this->assertNotNull($route);
        $this->assertSame('connections/{user}/request', $route->uri());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertNotContains('auth', $route->gatherMiddleware());
    }

    #[Test]
    public function route_macro_accepts_a_custom_controller(): void
    {
        Route::fellowship([
            'controller' => CustomFellowshipController::class,
            'middleware' => ['web'],
        ]);

        $route = Route::getRoutes()->getByName('fellowship.requests.store');

        $this->assertNotNull($route);
        $this->assertSame(CustomFellowshipController::class, $route->getControllerClass());
        $this->assertSame('send', $route->getActionMethod());
    }

    private function assertRoute(string $name, string $method, string $uri): void
    {
        $actions = [
            'fellowship.requests.store' => 'send',
            'fellowship.requests.accept' => 'accept',
            'fellowship.requests.deny' => 'deny',
            'fellowship.requests.cancel' => 'cancel',
            'fellowship.blocks.remove' => 'remove',
            'fellowship.blocks.store' => 'block',
            'fellowship.blocks.destroy' => 'unblock',
        ];

        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] was not registered.");
        $this->assertContains($method, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame(FellowshipController::class, $route->getControllerClass());
        $this->assertSame($actions[$name], $route->getActionMethod());
    }
}

class CustomFellowshipController extends FellowshipController
{
    // Test-only custom controller.
}
