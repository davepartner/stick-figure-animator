<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Get cleanup frequency from system settings
        $frequency = \App\Models\SystemSetting::get('cleanup_task_frequency', 'daily');

        $task = $schedule->command('videos:cleanup --force');

        // Apply frequency based on admin setting
        switch ($frequency) {
            case 'hourly':
                $task->hourly();
                break;
            case 'daily':
                $task->daily();
                break;
            case 'weekly':
                $task->weekly();
                break;
            case 'monthly':
                $task->monthly();
                break;
            default:
                $task->daily();
        }

        $task->withoutOverlapping();
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
