<?php

namespace EloquentWorks\Fellowship\Console\Commands;

use EloquentWorks\Fellowship\Events\FriendRequestExpired;
use EloquentWorks\Fellowship\Models\Fellowship;
use EloquentWorks\Fellowship\Status;
use Illuminate\Console\Command;

/**
 * Command to expire pending fellowship requests that are past their expiration date.
 */
class ExpireFellowshipRequestsCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'fellowships:expire {--chunk=100 : The number of records to process per chunk}';

    /** @var string The console command description. */
    protected $description = 'Expire pending fellowship requests that are past their expiration date.';

    /**
     * Handle the command execution.
     *
     * @return int Returns the exit status code.
     */
    public function handle(): int
    {
        $count = 0;

        $friendshipModel = config('friendships.models.friendship', Fellowship::class);

        // Get the chunk size from the command option.
        $chunkSize = max(1, (int) $this->option('chunk'));

        // Process expired pending friend requests in chunks to avoid memory issues.
        $friendshipModel::query()
            ->where('status', Status::PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById($chunkSize, function ($fellowships) use (&$count): void {
                // Update the status of each expired fellowship to EXPIRED and clear the accepted_at timestamp.
                foreach ($fellowships as $fellowship) {
                    $fellowship->forceFill([
                        'status' => Status::EXPIRED,
                        'accepted_at' => null,
                    ])->save();

                    $count++;

                    if (config('friendships.dispatch_events', true)) {
                        event(new FriendRequestExpired($fellowship));
                    }
                }
            });

        // Output the number of expired friend requests to the console.
        $this->info("Expired {$count} friend request(s).");

        // Return a success exit code.
        return self::SUCCESS;
    }
}
