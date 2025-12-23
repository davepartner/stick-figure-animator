<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // LLM Settings
            [
                'key' => 'default_text_model',
                'value' => 'deepseek-chat',
                'type' => 'string',
                'category' => 'llm',
                'description' => 'Default LLM for story generation',
            ],
            [
                'key' => 'default_image_model',
                'value' => 'segmind-consistent-character',
                'type' => 'string',
                'category' => 'llm',
                'description' => 'Default model for stick figure image generation',
            ],
            [
                'key' => 'default_voice_model',
                'value' => 'deepgram-aura',
                'type' => 'string',
                'category' => 'llm',
                'description' => 'Default TTS model for voiceover',
            ],
            
            // Pricing Settings (in credits)
            [
                'key' => 'text_model_deepseek_cost',
                'value' => '5',
                'type' => 'integer',
                'category' => 'pricing',
                'description' => 'Credits cost for DeepSeek text generation',
            ],
            [
                'key' => 'text_model_gpt4_cost',
                'value' => '20',
                'type' => 'integer',
                'category' => 'pricing',
                'description' => 'Credits cost for GPT-4 text generation',
            ],
            [
                'key' => 'image_model_segmind_cost_per_image',
                'value' => '2',
                'type' => 'integer',
                'category' => 'pricing',
                'description' => 'Credits cost per image for Segmind',
            ],
            [
                'key' => 'image_model_dalle_cost_per_image',
                'value' => '8',
                'type' => 'integer',
                'category' => 'pricing',
                'description' => 'Credits cost per image for DALL-E',
            ],
            [
                'key' => 'voice_model_deepgram_cost',
                'value' => '3',
                'type' => 'integer',
                'category' => 'pricing',
                'description' => 'Credits cost for Deepgram TTS',
            ],
            [
                'key' => 'voice_model_elevenlabs_cost',
                'value' => '10',
                'type' => 'integer',
                'category' => 'pricing',
                'description' => 'Credits cost for ElevenLabs TTS',
            ],
            
            // Cleanup Settings
            [
                'key' => 'video_cleanup_interval',
                'value' => '24',
                'type' => 'integer',
                'category' => 'cleanup',
                'description' => 'Hours before videos are deleted from server',
            ],
            [
                'key' => 'cleanup_task_frequency',
                'value' => 'hourly',
                'type' => 'string',
                'category' => 'cleanup',
                'description' => 'How often cleanup task runs (hourly, daily, weekly)',
            ],
            
            // General Settings
            [
                'key' => 'new_user_credits',
                'value' => '100',
                'type' => 'integer',
                'category' => 'general',
                'description' => 'Free credits for new user registration',
            ],
            [
                'key' => 'images_per_second',
                'value' => '0.33',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Number of images per second (0.33 = 1 image per 3 seconds)',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
