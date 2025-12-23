<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VoiceGenerationService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

    /**
     * Generate voiceover from script
     *
     * @param string $script The full narration text
     * @param string $model Model/voice to use
     * @return array ['audio_path' => string, 'cost' => float]
     */
    public function generateVoiceover(string $script, string $model = 'tts-1', string $voice = 'alloy'): array
    {
        try {
            // Use OpenAI TTS API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/audio/speech', [
                'model' => $model,
                'input' => $script,
                'voice' => $voice,
                'response_format' => 'mp3',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Voice generation API error: ' . $response->body());
            }

            // Save the audio file
            $audioContent = $response->body();
            $filename = 'audio/' . uniqid('voice_') . '.mp3';
            Storage::disk('public')->put($filename, $audioContent);

            // Calculate cost (approximate based on character count)
            $characterCount = strlen($script);
            $costPerCharacter = 0.000015; // OpenAI TTS-1 pricing
            $cost = $characterCount * $costPerCharacter;

            return [
                'audio_path' => Storage::disk('public')->path($filename),
                'cost' => $cost,
            ];

        } catch (\Exception $e) {
            Log::error('Voice generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get available voice models
     */
    public static function getAvailableModels(): array
    {
        return [
            'tts-1' => [
                'name' => 'OpenAI TTS Standard',
                'credits' => SystemSetting::get('voice_model_deepgram_cost', 3),
                'quality' => 'Standard',
                'voices' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'],
            ],
            'tts-1-hd' => [
                'name' => 'OpenAI TTS HD',
                'credits' => SystemSetting::get('voice_model_elevenlabs_cost', 10),
                'quality' => 'High',
                'voices' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'],
            ],
        ];
    }

    /**
     * Get available voices for a model
     */
    public static function getVoicesForModel(string $model): array
    {
        $models = self::getAvailableModels();
        return $models[$model]['voices'] ?? ['alloy'];
    }
}
