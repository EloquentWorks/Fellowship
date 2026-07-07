<?php

namespace Tests\Feature;

use EloquentWorks\Fellowship\Facades\Fellowship;
use EloquentWorks\Fellowship\Http\Controllers\FellowshipController;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteRegistrationTest extends TestCase
{
    #[Test]
    public function facade_registers_default_fellowship_routes(): void
    {
        Fellowship::routes();

        $this->assertRoute('fellowship.requests.store', 'POST', 'fellowship/{user}/request');
        $this->assertRoute('fellowship.requests.accept', 'POST', 'fellowship/{user}/accept');
        $this->assertRoute('fellowship.requests.deny', 'POST', 'fellowship/{user}/deny');
        $this->assertRoute('fellowship.requests.cancel', 'DELETE', 'fellowship/{user}/cancel');
        $this->assertRoute('fellowship.friends.remove', 'DELETE', 'fellowship/{user}/remove');
        $this->assertRoute('fellowship.blocks.store', 'POST', 'fellowship/{user}/block');
        $this->assertRoute('fellowship.blocks.destroy', 'DELETE', 'fellowship/{user}/unblock');
    }

    #[Test]
    public function facade_accepts_custom_route_options(): void
    {
        Fellowship::routes([
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
    public function facade_accepts_a_custom_controller(): void
    {
        Fellowship::routes([
            'controller' => CustomFellowshipController::class,
            'middleware' => ['web'],
        ]);

        $route = Route::getRoutes()->getByName('fellowship.requests.store');

        $this->assertSame(CustomFellowshipController::class.'@send', $route->getActionName());
    }

    private function assertRoute(string $name, string $method, string $uri): void
    {
        $actions = [
            'fellowship.requests.store' => 'send',
            'fellowship.requests.accept' => 'accept',
            'fellowship.requests.deny' => 'deny',
            'fellowship.requests.cancel' => 'cancel',
            'fellowship.friends.remove' => 'remove',
            'fellowship.blocks.store' => 'block',
            'fellowship.blocks.destroy' => 'unblock',
        ];

        $route = Route::getRoutes()->getByName($name);

        $this->assertNotNull($route, "Route [{$name}] was not registered.");
        $this->assertContains($method, $route->methods());
        $this->assertSame($uri, $route->uri());
        $this->assertSame(FellowshipController::class.'@'.$actions[$name], $route->getActionName());
    }
}

class CustomFellowshipController extends FellowshipController
{
    // Test-only custom controller.
}
