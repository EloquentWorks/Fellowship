<?php

namespace PixelError\Friendships\Console\Commands;

use Illuminate\Console\Command;
use PixelError\Friendships\Events\FriendRequestExpired;
use PixelError\Friendships\Models\Friendship;
use PixelError\Friendships\Status;

/**
 * Command to expire pending friend requests that are past their expiration date.
 */
class ExpireFriendRequestsCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'friendships:expire {--chunk=100 : The number of records to process per chunk}';

    /** @var string The console command description. */
    protected $description = 'Expire pending friend requests that are past their expiration date.';

    /**
     * Handle the command execution.
     *
     * @return int Returns the exit status code.
     */
    public function handle(): int
    {
        $count = 0;

        $friendshipModel = config('friendships.models.friendship', Friendship::class);

        // Get the chunk size from the command option.
        $chunkSize = max(1, (int) $this->option('chunk'));

        // Process expired pending friend requests in chunks to avoid memory issues.
        $friendshipModel::query()
            ->where('status', Status::PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById($chunkSize, function ($friendships) use (&$count): void {
                // Update the status of each expired friendship to EXPIRED and clear the accepted_at timestamp.
                foreach ($friendships as $friendship) {
                    $friendship->forceFill([
                        'status' => Status::EXPIRED,
                        'accepted_at' => null,
                    ])->save();

                    $count++;

                    if (config('friendships.dispatch_events', true)) {
                        event(new FriendRequestExpired($friendship));
                    }
                }
            });

        // Output the number of expired friend requests to the console.
        $this->info("Expired {$count} friend request(s).");

        // Return a success exit code.
        return self::SUCCESS;
    }
}
