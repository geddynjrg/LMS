<?php

namespace App\Console\Commands;

use App\Jobs\SyncApplicationStatusToDrupal;
use App\Models\TempApplication;
use Illuminate\Console\Command;

class SyncPendingToDrupal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drupal:sync-pending';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all pending status changes to Drupal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pending = TempApplication::where('lms_to_loan_app_sync_status', 'pending')
            ->limit(50)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending syncs found.');
            return;
        }

        $this->info("Found {$pending->count()} pending syncs. Dispatching jobs...");

        foreach ($pending as $app) {
            SyncApplicationStatusToDrupal::dispatch($app->id);
        }

        $this->info('All pending syncs dispatched to queue.');
    }
}
