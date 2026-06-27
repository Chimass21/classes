<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey = null;
    protected string $apiUrl;

    public function __construct()
    {
        $key = config('services.gemini.api_key');
        $this->apiKey = (is_string($key) && $key !== '') ? $key : null;
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    public function generate(string $prompt): string
    {
        if (empty($this->apiKey)) {
            return $this->fallbackGenerate($prompt);
        }

        try {
            $response = Http::timeout(60)->withOptions([
                'verify' => false,
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 8192,
                    'topP' => 0.95,
                ],
                'safetySettings' => [
                    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                return $this->cleanJsonResponse($text);
            }

            Log::error('Gemini API error: ' . $response->body());
            return $this->fallbackGenerate($prompt);

        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return $this->fallbackGenerate($prompt);
        }
    }

    protected function cleanJsonResponse(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        return trim($text);
    }

    protected function fallbackGenerate(string $prompt): string
    {
        return '';
    }
}
