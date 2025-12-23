<?php

namespace App\Console\Commands;

use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired videos to save server space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting video cleanup process...');

        // Find all expired videos that haven't been deleted yet
        $expiredVideos = Video::where('is_deleted', false)
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        if ($expiredVideos->isEmpty()) {
            $this->info('No expired videos found.');
            return 0;
        }

        $this->info("Found {$expiredVideos->count()} expired videos.");

        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with deletion?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        $deletedCount = 0;
        $totalSpaceFreed = 0;

        foreach ($expiredVideos as $video) {
            try {
                $fileSize = 0;

                // Delete video file
                if (file_exists($video->file_path)) {
                    $fileSize += filesize($video->file_path);
                    unlink($video->file_path);
                    $this->line("Deleted video: {$video->file_path}");
                }

                // Delete audio file
                if (file_exists($video->audio_path)) {
                    $fileSize += filesize($video->audio_path);
                    unlink($video->audio_path);
                }

                // Delete image files
                if ($video->image_paths) {
                    foreach ($video->image_paths as $imagePath) {
                        if (file_exists($imagePath)) {
                            $fileSize += filesize($imagePath);
                            unlink($imagePath);
                        }
                    }
                }

                // Mark as deleted
                $video->is_deleted = true;
                $video->save();

                $deletedCount++;
                $totalSpaceFreed += $fileSize;

                Log::info("Cleaned up video ID {$video->id}, freed " . number_format($fileSize / 1024 / 1024, 2) . " MB");

            } catch (\Exception $e) {
                $this->error("Failed to delete video ID {$video->id}: " . $e->getMessage());
                Log::error("Video cleanup failed for ID {$video->id}: " . $e->getMessage());
            }
        }

        $spaceMB = number_format($totalSpaceFreed / 1024 / 1024, 2);
        $this->info("Cleanup complete!");
        $this->info("Deleted {$deletedCount} videos");
        $this->info("Freed {$spaceMB} MB of disk space");

        Log::info("Video cleanup completed: {$deletedCount} videos deleted, {$spaceMB} MB freed");

        return 0;
    }
}
