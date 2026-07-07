<?php

namespace Tests\Feature;

use EloquentWorks\Fellowship\Events\FellowshipRequestExpired;
use EloquentWorks\Fellowship\Models\Fellowship;
use EloquentWorks\Fellowship\Status;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FellowshipCommandsTest extends TestCase
{
    #[Test]
    public function install_command_prints_the_route_snippet(): void
    {
        $this->artisan('fellowship:install')
            ->expectsOutputToContain('Installing Fellowship')
            ->expectsOutputToContain('use EloquentWorks\\Fellowship\\Facades\\Fellowship;')
            ->expectsOutputToContain('Fellowship::routes();')
            ->assertExitCode(0);
    }

    #[Test]
    public function install_command_accepts_force_and_routes_options(): void
    {
        $this->artisan('fellowship:install', [
            '--force' => true,
            '--routes' => true,
        ])->assertExitCode(0);
    }

    #[Test]
    public function expire_command_expires_old_pending_requests(): void
    {
        Event::fake();

        $sender = createUser();
        $recipient = createUser();
        $activeRecipient = createUser();

        $sender->sendFriendRequestTo($recipient)->forceFill([
            'expires_at' => now()->subHour(),
        ])->save();

        $sender->sendFriendRequestTo($activeRecipient)->forceFill([
            'expires_at' => now()->addHour(),
        ])->save();

        $this->artisan('fellowships:expire', ['--chunk' => 1])
            ->expectsOutput('Expired 1 fellowship request(s).')
            ->assertExitCode(0);

        $this->assertSame(1, Fellowship::query()->where('status', Status::EXPIRED)->count());
        $this->assertSame(1, Fellowship::query()->where('status', Status::PENDING)->count());

        Event::assertDispatched(FellowshipRequestExpired::class);
    }

    #[Test]
    public function expire_command_can_run_when_no_requests_are_expired(): void
    {
        $this->artisan('fellowships:expire')
            ->expectsOutput('Expired 0 fellowship request(s).')
            ->assertExitCode(0);
    }
}
