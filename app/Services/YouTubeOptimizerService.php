<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeOptimizerService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->baseUrl = env('OPENAI_BASE_URL', 'https://api.openai.com/v1');
    }

    /**
     * Generate YouTube optimized content
     *
     * @param string $script The video script
     * @param string $prompt The original user prompt
     * @return array ['titles' => array, 'description' => string, 'hashtags' => string]
     */
    public function generateYouTubeContent(string $script, string $prompt): array
    {
        try {
            $systemPrompt = "You are a YouTube SEO expert who creates viral, engaging titles, descriptions, and hashtags that maximize views and engagement.";

            $userPrompt = "Based on this video content, create YouTube optimization:\n\n";
            $userPrompt .= "Original Idea: {$prompt}\n";
            $userPrompt .= "Video Script: {$script}\n\n";
            $userPrompt .= "Create:\n";
            $userPrompt .= "1. THREE different title options (each under 70 characters, clickable, engaging)\n";
            $userPrompt .= "2. Rate each title's virality potential (1-10)\n";
            $userPrompt .= "3. A compelling description (150-200 words) with keywords\n";
            $userPrompt .= "4. 15-20 relevant hashtags (comma-separated)\n\n";
            $userPrompt .= "Format as JSON:\n";
            $userPrompt .= "{\n";
            $userPrompt .= "  \"titles\": [\n";
            $userPrompt .= "    {\"title\": \"...\", \"virality_score\": 8},\n";
            $userPrompt .= "    {\"title\": \"...\", \"virality_score\": 9},\n";
            $userPrompt .= "    {\"title\": \"...\", \"virality_score\": 7}\n";
            $userPrompt .= "  ],\n";
            $userPrompt .= "  \"description\": \"...\",\n";
            $userPrompt .= "  \"hashtags\": \"#tag1, #tag2, #tag3...\"\n";
            $userPrompt .= "}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-4.1-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.9, // Higher creativity for titles
            ]);

            if (!$response->successful()) {
                throw new \Exception('YouTube optimization API error: ' . $response->body());
            }

            $data = $response->json();
            $content = json_decode($data['choices'][0]['message']['content'], true);

            return [
                'titles' => $content['titles'],
                'description' => $content['description'],
                'hashtags' => $content['hashtags'],
            ];

        } catch (\Exception $e) {
            Log::error('YouTube optimization failed: ' . $e->getMessage());
            
            // Return fallback content
            return [
                'titles' => [
                    ['title' => $prompt, 'virality_score' => 5],
                    ['title' => 'Amazing Stick Figure Animation', 'virality_score' => 5],
                    ['title' => 'Watch This Incredible Story', 'virality_score' => 5],
                ],
                'description' => $script,
                'hashtags' => '#animation, #stickfigure, #viral, #shorts',
            ];
        }
    }
}
