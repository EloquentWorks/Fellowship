<?php

namespace Tests\Unit;

use EloquentWorks\Fellowship\Facades\Fellowship as FellowshipFacade;
use EloquentWorks\Fellowship\Fellowship;
use EloquentWorks\Fellowship\FellowshipServiceProvider;
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

    #[Test]
    public function backwards_compatible_provider_alias_extends_the_main_provider(): void
    {
        $this->assertTrue(is_subclass_of(
            \EloquentWorks\Fellowship\FriendshipsServiceProvider::class,
            FellowshipServiceProvider::class,
        ));
    }
}
