<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('group-loans:update-default-status --skip-loan-level')
            ->dailyAt('01:00')
            // ->hourly()
            // ->everyFiveMinutes()
            // ->appendOutputTo(storage_path('logs/loan_command.log'));
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('drupal:sync-pending --reconcile')
            ->everyTwoHours()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('training:auto-reject-missed')
            ->dailyAt('06:00')
            ->timezone('Africa/Nairobi')
            ->withoutOverlapping()
            ->runInBackground();

        // Run the backup Sunday at 2 AM
        $schedule->command('backup:run --only-db')
            ->sundays()
            ->at('03:00')
            ->timezone('Africa/Nairobi')
            ->onSuccess(function () {
                \Log::info("Sunday Database Backup Successful for Uwezo Fund.");
            })
            ->onFailure(function () {
                \Log::error("Sunday Database Backup FAILED.");
            })
            ->appendOutputTo(storage_path('logs/backup_command.log'));

        // Run the cleanup Sunday at 1 AM
        $schedule->command('backup:clean')
            ->sundays()
            ->at('02:00')
            ->timezone('Africa/Nairobi')
            ->onSuccess(function () {
                \Log::info("Sunday Database Backup Successful for Uwezo Fund.");
            })
            ->onFailure(function () {
                \Log::error("Sunday Database Backup FAILED.");
            })
            ->appendOutputTo(storage_path('logs/backup_clean_command.log'));

        $schedule->call(function () {
            $cutoff = now()->subDays(30);
            DB::table('idempotency_keys')
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->chunkById(1000, function ($keys) {
                    $ids = $keys->pluck('id')->toArray();

                    DB::table('idempotency_keys')->whereIn('id', $ids)->delete();
                });
        })->daily();

        $schedule->call(function () {
            $stuckCutoff = now()->subHours(2);
            $clearedCount = DB::table('idempotency_keys')
                ->where('status', 'processing')
                ->where('created_at', '<', $stuckCutoff)
                ->delete();

            if ($clearedCount > 0) {
                Log::warning("Idempotency Safety: Cleared {$clearedCount} stuck locks older than 2 hours.");
            }
        })->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
