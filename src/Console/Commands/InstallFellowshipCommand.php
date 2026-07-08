<?php

namespace EloquentWorks\Fellowship\Console\Commands;

use Illuminate\Console\Command;

class InstallFellowshipCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'fellowship:install
        {--force : Overwrite any existing published files}
        {--routes : Publish a copyable Fellowship route snippet}';

    /** @var string The description of the command. */
    protected $description = 'Install the Fellowship package by publishing its config, migrations, and optional route snippet.';

    /**
     * Execute the console command.
     *
     * @return int Returns the exit status code.
     */
    public function handle(): int
    {
        // Determine if the --force option was provided to overwrite existing files.
        $force = (bool) $this->option('force');

        // Inform the user that the installation process is starting.
        $this->components->info('Installing Fellowship...');

        // Publish the package's configuration file to the application's config directory.
        $this->callSilent('vendor:publish', [
            '--tag' => 'fellowship-config',
            '--force' => $force,
        ]);

        // Publish the package's migration files to the application's database/migrations directory.
        $this->callSilent('vendor:publish', [
            '--tag' => 'fellowship-migrations',
            '--force' => $force,
        ]);

        // If the --routes option is provided, publish the route snippet to the application's routes directory.
        if ((bool) $this->option('routes')) {
            $this->callSilent('vendor:publish', [
                '--tag' => 'fellowship-routes',
                '--force' => $force,
            ]);
        }

        // Inform the user that the installation process is complete and provide instructions for adding routes.
        $this->newLine();
        $this->components->info('Add this to routes/web.php when you want the web routes:');
        $this->line('use Illuminate\Support\Facades\Route;');
        $this->newLine();
        $this->line('Route::fellowship();');
        $this->newLine();

        // Inform the user that the installation was successful.
        $this->components->success('Fellowship installed successfully.');

        // Return a success exit code.
        return self::SUCCESS;
    }
}
