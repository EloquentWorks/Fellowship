<?php

namespace Tests\Unit;

use EloquentWorks\Fellowship\Facades\Fellowship as FellowshipFacade;
use EloquentWorks\Fellowship\Fellowship;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FellowshipServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_the_fellowship_singleton_and_facade(): void
    {
        $this->assertTrue($this->app->bound('fellowship'));
        $this->assertInstanceOf(Fellowship::class, $this->app->make('fellowship'));
        $this->assertInstanceOf(Fellowship::class, FellowshipFacade::getFacadeRoot());
    }

    #[Test]
    public function it_merges_package_configuration(): void
    {
        $this->assertSame('friendships', config('fellowship.tables.friendships'));
        $this->assertSame('fellowship', config('fellowship.routes.prefix'));
        $this->assertSame(['web', 'auth'], config('fellowship.routes.middleware'));
    }

    #[Test]
    public function it_does_not_register_web_routes_automatically(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('fellowship.requests.store'));
    }
}
