<?php

namespace App\Console\Commands;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FailStuckVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:fail-stuck {--timeout=30 : Timeout in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically fail videos stuck in pending/processing status for too long';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutMinutes = (int) $this->option('timeout');
        $cutoffTime = Carbon::now()->subMinutes($timeoutMinutes);

        $this->info("Looking for videos stuck in pending/processing status for more than {$timeoutMinutes} minutes...");

        // Find stuck videos
        $stuckVideos = Prompt::whereIn('status', ['pending', 'processing'])
            ->where('created_at', '<', $cutoffTime)
            ->get();

        if ($stuckVideos->isEmpty()) {
            $this->info('No stuck videos found.');
            return 0;
        }

        $this->info("Found {$stuckVideos->count()} stuck video(s).");

        $refundedCredits = 0;
        $failedCount = 0;

        foreach ($stuckVideos as $prompt) {
            $this->line("Processing Prompt ID {$prompt->id} (stuck for " . $prompt->created_at->diffForHumans() . ")");

            // Refund credits to user
            $user = User::find($prompt->user_id);
            if ($user) {
                $user->addCredits($prompt->credits_used);
                $refundedCredits += $prompt->credits_used;
                $this->line("  → Refunded {$prompt->credits_used} credits to {$user->email}");
            }

            // Mark as failed
            $prompt->update([
                'status' => 'failed',
                'error_message' => "Video generation timed out after {$timeoutMinutes} minutes. This usually means the queue worker is not running. Your credits have been refunded. Please ensure the queue worker is running (php artisan queue:work) and try again.",
            ]);

            $failedCount++;
            $this->line("  → Marked as failed");
        }

        $this->info("\nCompleted!");
        $this->info("Failed videos: {$failedCount}");
        $this->info("Total credits refunded: {$refundedCredits}");

        return 0;
    }
}
