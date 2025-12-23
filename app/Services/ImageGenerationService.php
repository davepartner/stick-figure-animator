<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageGenerationService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Generate stick figure images for scenes
     *
     * @param array $scenes Array of scene descriptions
     * @param string $model Model to use
     * @return array ['images' => array of file paths, 'cost' => float]
     */
    public function generateImages(array $scenes, string $model = 'dall-e-3'): array
    {
        $imagePaths = [];
        $totalCost = 0;

        // Create a consistent character description to use across all images
        $characterStyle = "Simple black stick figure with round head and expressive face. Minimalist style, clean lines, white background.";

        foreach ($scenes as $index => $scene) {
            try {
                $imageData = $this->generateSingleImage(
                    $scene['description'],
                    $characterStyle,
                    $model
                );

                $imagePaths[] = $imageData['path'];
                $totalCost += $imageData['cost'];

                // Small delay to avoid rate limiting
                if ($index < count($scenes) - 1) {
                    usleep(500000); // 0.5 second delay
                }

            } catch (\Exception $e) {
                Log::error("Image generation failed for scene {$index}: " . $e->getMessage());
                throw $e;
            }
        }

        return [
            'images' => $imagePaths,
            'cost' => $totalCost,
        ];
    }

    /**
     * Generate a single image
     */
    protected function generateSingleImage(string $sceneDescription, string $characterStyle, string $model): array
    {
        // Combine character style with scene description
        $fullPrompt = "{$characterStyle}\n\nScene: {$sceneDescription}";

        // For DALL-E 3
        if ($model === 'dall-e-3') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $fullPrompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Image generation API error: ' . $response->body());
            }

            $data = $response->json();
            $imageUrl = $data['data'][0]['url'];

            // Download and save the image
            $imageContent = file_get_contents($imageUrl);
            $filename = 'images/' . uniqid('scene_') . '.png';
            Storage::disk('public')->put($filename, $imageContent);

            $cost = 0.04; // DALL-E 3 standard cost

            return [
                'path' => Storage::disk('public')->path($filename),
                'cost' => $cost,
            ];
        }

        // For other models (placeholder - you can add Segmind, etc.)
        throw new \Exception("Model {$model} not yet implemented");
    }

    /**
     * Get available image models
     */
    public static function getAvailableModels(): array
    {
        return [
            'dall-e-3' => [
                'name' => 'DALL-E 3',
                'credits_per_image' => SystemSetting::get('image_model_dalle_cost_per_image', 8),
                'quality' => 'High',
            ],
            'segmind-consistent' => [
                'name' => 'Segmind Consistent Character',
                'credits_per_image' => SystemSetting::get('image_model_segmind_cost_per_image', 2),
                'quality' => 'Standard',
            ],
        ];
    }
}
