<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextGenerationService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->baseUrl = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
    }

    /**
     * Generate a story script from a prompt
     *
     * @param string $prompt User's story idea
     * @param int $durationSeconds Desired video duration
     * @param string $model Model to use (e.g., 'gpt-4.1-mini', 'deepseek-chat')
     * @return array ['script' => string, 'scenes' => array, 'cost' => float]
     */
    public function generateStory(string $prompt, int $durationSeconds, string $model = 'gpt-4.1-mini'): array
    {
        try {
            // Check if API key is configured
            if (empty($this->apiKey)) {
                throw new \Exception('OpenAI API key is not configured. Please add OPENAI_API_KEY to your .env file.');
            }
            // Calculate number of scenes based on duration
            $imagesPerSecond = (float) SystemSetting::get('images_per_second', 0.33);
            $numberOfScenes = (int) ceil($durationSeconds * $imagesPerSecond);

            // Create the system prompt
            $systemPrompt = "You are a creative storyteller who creates engaging short stories for stick figure animations. Your stories should be visual, simple, and suitable for stick figure illustrations.";

            // Create the user prompt
            $userPrompt = "Create a {$durationSeconds}-second story based on this idea: \"{$prompt}\"\n\n";
            $userPrompt .= "Requirements:\n";
            $userPrompt .= "1. Write a complete story that can be narrated in exactly {$durationSeconds} seconds\n";
            $userPrompt .= "2. Break the story into exactly {$numberOfScenes} distinct visual scenes\n";
            $userPrompt .= "3. Each scene should be simple enough to be illustrated with stick figures\n\n";
            $userPrompt .= "Format your response as JSON with this structure:\n";
            $userPrompt .= "{\n";
            $userPrompt .= "  \"full_script\": \"The complete narration text\",\n";
            $userPrompt .= "  \"scenes\": [\n";
            $userPrompt .= "    {\n";
            $userPrompt .= "      \"description\": \"Visual description of what to show (for image generation)\",\n";
            $userPrompt .= "      \"narration\": \"What is being said during this scene\"\n";
            $userPrompt .= "    }\n";
            $userPrompt .= "  ]\n";
            $userPrompt .= "}";

            // Make API request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.8,
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
                
                // Provide user-friendly error messages
                if (str_contains($errorMessage, 'API key')) {
                    throw new \Exception('API key error: Please configure a valid OpenAI API key in your settings.');
                } elseif (str_contains($errorMessage, 'quota')) {
                    throw new \Exception('API quota exceeded: Your OpenAI account has reached its usage limit. Please check your billing.');
                } elseif (str_contains($errorMessage, 'model')) {
                    throw new \Exception('Model error: The selected AI model is not available. Please try a different model.');
                } else {
                    throw new \Exception('AI service error: Unable to generate story. Please try again later.');
                }
            }

            $data = $response->json();
            $content = json_decode($data['choices'][0]['message']['content'], true);

            // Calculate cost (approximate)
            $tokensUsed = $data['usage']['total_tokens'] ?? 1000;
            $costPerToken = $this->getModelCostPerToken($model);
            $actualCost = ($tokensUsed / 1000) * $costPerToken;

            return [
                'script' => $content['full_script'],
                'scenes' => $content['scenes'],
                'cost' => $actualCost,
            ];

        } catch (\Exception $e) {
            Log::error('Text generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the cost per 1000 tokens for a model
     */
    protected function getModelCostPerToken(string $model): float
    {
        // Approximate costs per 1000 tokens (you can make this configurable)
        $costs = [
            'gpt-4.1-mini' => 0.0001,
            'gpt-4.1-nano' => 0.00005,
            'deepseek-chat' => 0.00003,
            'gemini-2.5-flash' => 0.00002,
        ];

        return $costs[$model] ?? 0.0001;
    }

    /**
     * Get available text models
     */
    public static function getAvailableModels(): array
    {
        return [
            'gpt-4.1-mini' => [
                'name' => 'GPT-4.1 Mini',
                'credits' => SystemSetting::get('text_model_gpt4_cost', 20),
                'quality' => 'High',
            ],
            'gpt-4.1-nano' => [
                'name' => 'GPT-4.1 Nano',
                'credits' => SystemSetting::get('text_model_deepseek_cost', 5),
                'quality' => 'Standard',
            ],
            'deepseek-chat' => [
                'name' => 'DeepSeek Chat',
                'credits' => SystemSetting::get('text_model_deepseek_cost', 5),
                'quality' => 'Standard',
            ],
        ];
    }
}
