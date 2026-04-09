<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Artisan;

class Initialise extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:initialise';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialise application: clear caches, reset storage link, delete log file';

    /**
     * Verify if artisan command exists
     */
    protected function artisanCommandExists(string $command): bool
    {
        return collect(Artisan::all())->has($command);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing application caches');
        $this->call('cache:clear', []);
        $this->call('view:clear', []);
        $this->call('route:clear', []);
        $this->call('event:clear', []);
        $this->call('config:clear', []);
        $this->call('optimize:clear', []);

        // clear queues
        if ($this->artisanCommandExists('queue:clear')) {
            $this->call('queue:clear');
            $this->info('Queues cleared...');
        } else {
            $this->warn('queue:clear command not available. Skipping...');
        }

        // clear auth reset tokens
        if ($this->artisanCommandExists('auth:clear-resets')) {
            $this->call('auth:clear-resets');
            $this->info('Expired password reset tokens cleared.');
        } else {
            $this->warn('auth:clear-resets command not available. Skipping...');
        }

        // clear the debugbar data if installed
        if ($this->artisanCommandExists('debugbar:clear')) {
            $this->call('debugbar:clear');
            $this->info('Debugbar cache cleared.');
        } else {
            $this->warn('debugbar:clear command not found. Skipping...');
        }

        // run npm build
        $this->buildAssets();

        // check if the user wants to delete the log file
        $this->deleteLog();

        // recreate the storage link
        $this->info('');
        $this->info('Initialising storage link...');
        $this->call('storage:unlink');
        $this->call('storage:link', ['--force' => true]);

        return Command::SUCCESS;
    }

    public function buildAssets()
    {
        $this->info('Running npm run build...');

        $result = Process::path(base_path())->run('npm run build');

        if ($result->successful()) {
            $this->info('Build completed successfully!');
        } else {
            $this->error('Build failed:');
            $this->error($result->errorOutput());
            return Command::FAILURE;
        }
    }

    public function deleteLog()
    {
        if ($this->confirm('Do you want to delete the Laravel log file?', false)) {

            $logPath = storage_path('logs/laravel.log');
            if (File::exists($logPath)) {
                File::delete($logPath);
                $this->info('Log file deleted successfully.');
            } else {
                $this->warn('Log file does not exist.');
            }
        } else {
            $this->line('Log file was not deleted.');
        }
    }
}
