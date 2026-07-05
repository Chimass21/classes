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
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent';
    }

    public function generate(string $prompt): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key not configured. Set GEMINI_API_KEY in .env');
            throw new \RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        try {
            Log::debug('Gemini API Request', [
                'prompt_length' => strlen($prompt),
                'prompt_preview' => substr($prompt, 0, 1000),
            ]);

            $response = Http::timeout(120)->withOptions([
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
                    'temperature' => 0.9,
                    'maxOutputTokens' => 16384,
                    'topP' => 0.95,
                    'topK' => 40,
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

                Log::debug('Gemini API Response', [
                    'response_length' => strlen($text),
                    'response_preview' => substr($text, 0, 500),
                    'finish_reason' => $data['candidates'][0]['finishReason'] ?? 'unknown',
                    'token_count' => $data['usageMetadata']['candidatesTokenCount'] ?? 'unknown',
                    'prompt_token_count' => $data['usageMetadata']['promptTokenCount'] ?? 'unknown',
                    'total_token_count' => $data['usageMetadata']['totalTokenCount'] ?? 'unknown',
                ]);

                if (($data['candidates'][0]['finishReason'] ?? '') === 'SAFETY') {
                    Log::warning('Gemini response blocked by safety filters', [
                        'safety_ratings' => $data['candidates'][0]['safetyRatings'] ?? [],
                    ]);
                    throw new \RuntimeException('Content generation was blocked by AI safety filters. Please rephrase your topic.');
                }

                if (empty(trim($text))) {
                    Log::warning('Gemini returned empty response', ['full_response' => $data]);
                    throw new \RuntimeException('The AI returned an empty response. Please try again.');
                }

                return $this->cleanJsonResponse($text);
            }

            $errorBody = $response->body();
            Log::error('Gemini API HTTP error', [
                'status' => $response->status(),
                'body' => substr($errorBody, 0, 2000),
            ]);

            throw new \RuntimeException('Gemini API returned status ' . $response->status() . ': ' . substr($errorBody, 0, 500));

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Gemini API connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Failed to connect to Gemini API: ' . $e->getMessage());
        }
    }

    protected function cleanJsonResponse(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        return trim($text);
    }
}
