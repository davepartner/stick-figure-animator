<?php

namespace App\Jobs;

use App\Models\Prompt;
use App\Models\Video;
use App\Models\Transaction;
use App\Models\SystemSetting;
use App\Services\TextGenerationService;
use App\Services\ImageGenerationService;
use App\Services\VoiceGenerationService;
use App\Services\VideoAssemblyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VideoGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 1; // Don't retry on failure

    protected $promptId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $promptId)
    {
        $this->promptId = $promptId;
    }    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Increase PHP execution time limit for this job
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', '600');
        
        $prompt = Prompt::findOrFail($this->promptId);

        try {
            // Update status to processing
            $prompt->update(['status' => 'processing']);

            Log::info("Starting video generation for prompt ID: {$this->promptId}");

            $totalCost = 0;

            // Step 1: Generate story script
            Log::info("Generating story script...");
            $textService = new TextGenerationService();
            $storyResult = $textService->generateStory(
                $prompt->original_prompt,
                $prompt->duration_seconds,
                $prompt->text_model ?? SystemSetting::get('default_text_model', 'gpt-4.1-nano')
            );

            $prompt->update([
                'generated_script' => $storyResult['script'],
                'scene_descriptions' => $storyResult['scenes'],
            ]);

            $totalCost += $storyResult['cost'];
            Log::info("Story generated. Cost: $" . $storyResult['cost']);

            // Step 2: Generate voiceover
            Log::info("Generating voiceover...");
            $voiceService = new VoiceGenerationService();
            $voiceResult = $voiceService->generateVoiceover(
                $storyResult['script'],
                $prompt->voice_model ?? 'tts-1'
            );

            $totalCost += $voiceResult['cost'];
            Log::info("Voiceover generated. Cost: $" . $voiceResult['cost']);

            // Step 3: Generate images for each scene
            Log::info("Generating images for " . count($storyResult['scenes']) . " scenes...");
            $imageService = new ImageGenerationService();
            $imageResult = $imageService->generateImages(
                $storyResult['scenes'],
                $prompt->image_model ?? 'dall-e-3'
            );

            $totalCost += $imageResult['cost'];
            Log::info("Images generated. Cost: $" . $imageResult['cost']);

            // Step 4: Assemble video
            Log::info("Assembling video...");
            $videoService = new VideoAssemblyService();
            $videoResult = $videoService->assembleVideo(
                $imageResult['images'],
                $voiceResult['audio_path'],
                $prompt->duration_seconds
            );

            Log::info("Video assembled successfully");

            // Step 5: Calculate expiration time
            $cleanupInterval = (int) SystemSetting::get('video_cleanup_interval', 24);
            $expiresAt = Carbon::now()->addHours($cleanupInterval);

            // Step 6: Save video record
            $video = Video::create([
                'prompt_id' => $prompt->id,
                'file_path' => $videoResult['video_path'],
                'audio_path' => $voiceResult['audio_path'],
                'image_paths' => $imageResult['images'],
                'duration_seconds' => $prompt->duration_seconds,
                'file_size' => $videoResult['file_size'],
                'expires_at' => $expiresAt,
            ]);

            // Step 7: Update prompt with costs
            $prompt->update([
                'actual_cost' => $totalCost,
                'status' => 'completed',
            ]);

            // Step 8: Record transaction
            Transaction::recordUsage(
                $prompt->user_id,
                $prompt->credits_used,
                $totalCost,
                "Video generation: {$prompt->original_prompt}"
            );

            Log::info("Video generation completed successfully. Total cost: $" . $totalCost);

        } catch (\Exception $e) {
            Log::error("Video generation failed for prompt ID {$this->promptId}: " . $e->getMessage());
            
            $prompt->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Refund credits to user if deducted
            if ($prompt->credits_used > 0) {
                $prompt->user->addCredits($prompt->credits_used);
                Log::info("Refunded {$prompt->credits_used} credits to user {$prompt->user_id}");
            }

            throw $e;
        }
    }
}
