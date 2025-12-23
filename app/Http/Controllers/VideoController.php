<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\Video;
use App\Models\SystemSetting;
use App\Jobs\VideoGenerationJob;
use App\Services\TextGenerationService;
use App\Services\ImageGenerationService;
use App\Services\VoiceGenerationService;
use App\Services\YouTubeOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    /**
     * Display the video creation dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's recent prompts
        $recentPrompts = Prompt::where('user_id', $user->id)
            ->with('video')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get available models
        $textModels = TextGenerationService::getAvailableModels();
        $imageModels = ImageGenerationService::getAvailableModels();
        $voiceModels = VoiceGenerationService::getAvailableModels();

        return view('videos.create', compact(
            'recentPrompts',
            'textModels',
            'imageModels',
            'voiceModels'
        ));
    }

    /**
     * Calculate cost estimate for a video
     */
    public function estimateCost(Request $request)
    {
        $validated = $request->validate([
            'duration' => 'required|integer|min:10|max:600',
            'text_model' => 'required|string',
            'image_model' => 'required|string',
            'voice_model' => 'required|string',
        ]);

        $textModels = TextGenerationService::getAvailableModels();
        $imageModels = ImageGenerationService::getAvailableModels();
        $voiceModels = VoiceGenerationService::getAvailableModels();

        // Calculate number of images needed
        $imagesPerSecond = (float) SystemSetting::get('images_per_second', 0.33);
        $numberOfImages = (int) ceil($validated['duration'] * $imagesPerSecond);

        // Calculate total credits
        $textCredits = $textModels[$validated['text_model']]['credits'] ?? 0;
        $imageCredits = ($imageModels[$validated['image_model']]['credits_per_image'] ?? 0) * $numberOfImages;
        $voiceCredits = $voiceModels[$validated['voice_model']]['credits'] ?? 0;

        $totalCredits = $textCredits + $imageCredits + $voiceCredits;

        return response()->json([
            'total_credits' => $totalCredits,
            'breakdown' => [
                'text' => $textCredits,
                'images' => $imageCredits,
                'voice' => $voiceCredits,
                'image_count' => $numberOfImages,
            ],
        ]);
    }

    /**
     * Create a new video generation request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:500',
            'duration' => 'required|integer|min:10|max:600',
            'text_model' => 'required|string',
            'image_model' => 'required|string',
            'voice_model' => 'required|string',
        ]);

        $user = Auth::user();

        // Calculate credits needed
        $textModels = TextGenerationService::getAvailableModels();
        $imageModels = ImageGenerationService::getAvailableModels();
        $voiceModels = VoiceGenerationService::getAvailableModels();

        $imagesPerSecond = (float) SystemSetting::get('images_per_second', 0.33);
        $numberOfImages = (int) ceil($validated['duration'] * $imagesPerSecond);

        $textCredits = $textModels[$validated['text_model']]['credits'] ?? 0;
        $imageCredits = ($imageModels[$validated['image_model']]['credits_per_image'] ?? 0) * $numberOfImages;
        $voiceCredits = $voiceModels[$validated['voice_model']]['credits'] ?? 0;
        $totalCredits = $textCredits + $imageCredits + $voiceCredits;

        // Check if user has enough credits
        if (!$user->hasCredits($totalCredits)) {
            return redirect()->back()->with('error', 'Insufficient credits. You need ' . $totalCredits . ' credits but only have ' . $user->credits . '.');
        }

        // Deduct credits
        $user->deductCredits($totalCredits);

        // Create prompt record
        $prompt = Prompt::create([
            'user_id' => $user->id,
            'original_prompt' => $validated['prompt'],
            'duration_seconds' => $validated['duration'],
            'text_model' => $validated['text_model'],
            'image_model' => $validated['image_model'],
            'voice_model' => $validated['voice_model'],
            'credits_used' => $totalCredits,
            'status' => 'pending',
        ]);

        // Dispatch job to queue
        try {
            VideoGenerationJob::dispatch($prompt->id);
        } catch (\Exception $e) {
            // If job dispatch fails, refund credits and mark as failed
            $user->addCredits($totalCredits);
            $prompt->update([
                'status' => 'failed',
                'error_message' => 'Failed to queue video generation job: ' . $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to start video generation. Your credits have been refunded. Please try again.');
        }

        return redirect()->route('videos.show', $prompt->id)
            ->with('success', 'Video generation started! This may take 2-5 minutes. Please keep this page open.');
    }

    /**
     * Show video generation status and result
     */
    public function show($id)
    {
        $prompt = Prompt::with('video')->findOrFail($id);

        // Check if user owns this prompt
        if ($prompt->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('videos.show', compact('prompt'));
    }

    /**
     * Check status of video generation (for AJAX polling)
     */
    public function checkStatus($id)
    {
        $prompt = Prompt::with('video')->findOrFail($id);

        if ($prompt->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        return response()->json([
            'status' => $prompt->status,
            'error_message' => $prompt->error_message,
            'video' => $prompt->video ? [
                'id' => $prompt->video->id,
                'expires_at' => $prompt->video->expires_at->toIso8601String(),
                'time_remaining' => $prompt->video->getTimeRemaining(),
            ] : null,
        ]);
    }

    /**
     * Download generated video
     */
    public function download($id)
    {
        $video = Video::findOrFail($id);
        $prompt = $video->prompt;

        if ($prompt->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($video->is_deleted || !file_exists($video->file_path)) {
            return redirect()->back()->with('error', 'Video has been deleted or is no longer available.');
        }

        return response()->download($video->file_path, 'stick-figure-video-' . $video->id . '.mp4');
    }

    /**
     * Generate YouTube optimization for a video
     */
    public function generateYouTubeContent($id)
    {
        $video = Video::findOrFail($id);
        $prompt = $video->prompt;

        if ($prompt->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        try {
            $optimizer = new YouTubeOptimizerService();
            $result = $optimizer->generateYouTubeContent(
                $prompt->generated_script,
                $prompt->original_prompt
            );

            $video->update([
                'youtube_titles' => $result['titles'],
                'youtube_description' => $result['description'],
                'youtube_hashtags' => $result['hashtags'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate a video from an existing prompt
     */
    public function regenerate($id)
    {
        $oldPrompt = Prompt::findOrFail($id);

        if ($oldPrompt->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();

        // Check credits
        if (!$user->hasCredits($oldPrompt->credits_used)) {
            return redirect()->back()->with('error', 'Insufficient credits to regenerate this video.');
        }

        // Deduct credits
        $user->deductCredits($oldPrompt->credits_used);

        // Create new prompt with same settings
        $newPrompt = Prompt::create([
            'user_id' => $user->id,
            'original_prompt' => $oldPrompt->original_prompt,
            'duration_seconds' => $oldPrompt->duration_seconds,
            'text_model' => $oldPrompt->text_model,
            'image_model' => $oldPrompt->image_model,
            'voice_model' => $oldPrompt->voice_model,
            'credits_used' => $oldPrompt->credits_used,
            'status' => 'pending',
        ]);

        // Dispatch job
        VideoGenerationJob::dispatch($newPrompt->id);

        return redirect()->route('videos.show', $newPrompt->id)
            ->with('success', 'Video regeneration started!');
    }
}
