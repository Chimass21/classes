<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected ?string $apiKey = null;
    protected string $baseUrl;
    protected string $model;
    protected int $maxRetries;
    protected int $timeout;

    protected const SYSTEM_PROMPT = 'You are Brain, an expert Nigerian curriculum specialist and educational content creator. You generate high-quality, curriculum-aligned lesson plans, lesson notes, examination questions, and educational resources for Nigerian primary and secondary schools following NERDC/UBEC/WASSCE/NECO/JAMB standards. Always respond with accurate, well-structured content tailored for teachers and students.';

    public function __construct()
    {
        $key = config('services.openai.api_key');
        $this->apiKey = (is_string($key) && $key !== '') ? $key : null;
        $this->baseUrl = rtrim(config('services.openai.base_url', 'https://api.deepseek.com'), '/');
        $this->model = config('services.openai.model', 'deepseek-chat');
        $this->maxRetries = max(1, (int) config('services.openai.max_retries', 3));
        $this->timeout = max(30, (int) config('services.openai.timeout', 120));
    }

    public function generate(string $prompt, bool $jsonMode = false, int $maxTokens = 16384): string
    {
        if (empty($this->apiKey)) {
            Log::error('API key not configured. Set OPENAI_API_KEY in .env');
            throw new \RuntimeException('API key is not configured. Please set OPENAI_API_KEY in your .env file.');
        }

        $lastError = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                Log::debug('AI API Request', [
                    'model' => $this->model,
                    'json_mode' => $jsonMode,
                    'prompt_length' => strlen($prompt),
                    'attempt' => $attempt,
                ]);

                $payload = $this->buildPayload($prompt, $jsonMode, $maxTokens);

                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->apiKey,
                    ])
                    ->post($this->baseUrl . '/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['choices'][0]['message']['content'] ?? '';

                    $usage = $data['usage'] ?? [];
                    Log::debug('AI API Response', [
                        'model' => $this->model,
                        'response_length' => strlen($text),
                        'response_preview' => substr($text, 0, 500),
                        'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'unknown',
                        'prompt_tokens' => $usage['prompt_tokens'] ?? 'unknown',
                        'completion_tokens' => $usage['completion_tokens'] ?? 'unknown',
                        'total_tokens' => $usage['total_tokens'] ?? 'unknown',
                    ]);

                    if (empty(trim($text))) {
                        Log::warning('AI returned empty response');
                        throw new \RuntimeException('The AI returned an empty response. Please try again.');
                    }

                    $cleaned = $this->cleanJsonResponse($text);

                    // Log if cleaning changed the response or if it's still not valid JSON
                    if ($cleaned !== $text) {
                        Log::debug('AI response was cleaned', [
                            'original_preview' => substr($text, 0, 500),
                            'cleaned_preview' => substr($cleaned, 0, 500),
                        ]);
                    }

                    return $cleaned;
                }

                $statusCode = $response->status();
                $errorBody = $response->body();
                $errorJson = $response->json();
                $errorMessage = $errorJson['error']['message'] ?? $errorBody;

                if ($statusCode === 429) {
                    $lastError = new \RuntimeException('AI service is busy. Please wait 1 minute and try again.');
                    if ($attempt >= $this->maxRetries) {
                        throw $lastError;
                    }
                    $headerRetryAfter = $response->header('Retry-After');
                    $retryAfter = $headerRetryAfter ? (int)$headerRetryAfter : min(60, $attempt * 15);
                    Log::warning("Rate limited (attempt {$attempt}/{$this->maxRetries}), retrying in {$retryAfter}s");
                    sleep($retryAfter);
                    continue;
                }

                if ($statusCode >= 500) {
                    Log::warning("AI server error (attempt {$attempt}/{$this->maxRetries}): HTTP {$statusCode}");
                    if ($attempt < $this->maxRetries) {
                        sleep($attempt * 2);
                        $lastError = new \RuntimeException("AI server error: HTTP {$statusCode}");
                        continue;
                    }
                }

                Log::error('AI API HTTP error', [
                    'status' => $statusCode,
                    'body' => substr($errorBody, 0, 2000),
                ]);

                throw new \RuntimeException('AI API returned status ' . $statusCode . ': ' . substr($errorMessage, 0, 500));

            } catch (\RuntimeException $e) {
                $lastError = $e;
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }
                Log::warning("AI API attempt {$attempt} failed: {$e->getMessage()}");
            } catch (\Exception $e) {
                $lastError = $e;
                if ($attempt >= $this->maxRetries) {
                    Log::error('AI API connection error after all retries', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw new \RuntimeException('Failed to connect to AI API: ' . $e->getMessage());
                }
                Log::warning("AI connection attempt {$attempt} failed: {$e->getMessage()}, retrying...");
                sleep($attempt * 2);
            }
        }

        throw new \RuntimeException('AI API request failed after ' . $this->maxRetries . ' attempts: ' . ($lastError?->getMessage() ?? 'Unknown error'));
    }

    public function generateStream(string $prompt, callable $onChunk, bool $jsonMode = false): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('API key is not configured.');
        }

        $payload = $this->buildPayload($prompt, $jsonMode, 16384);
        $payload['stream'] = true;

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'text/event-stream',
            ])
            ->withOptions(['stream' => true])
            ->post($this->baseUrl . '/v1/chat/completions', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('AI streaming request failed: HTTP ' . $response->status());
        }

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(4096);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                $line = trim($line);

                if (empty($line)) continue;

                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    if (trim($json) === '[DONE]') return;

                    $data = json_decode($json, true);
                    if ($data && isset($data['choices'][0]['delta']['content'])) {
                        $onChunk($data['choices'][0]['delta']['content']);
                    }
                }
            }
        }
    }

    protected function buildPayload(string $prompt, bool $jsonMode, int $maxTokens): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => $maxTokens,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return $payload;
    }

    protected function cleanJsonResponse(string $text): string
    {
        $text = trim($text);

        if (empty($text)) {
            return '';
        }

        // Remove BOM characters
        $text = preg_replace('/^\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE/', '', $text);

        // Remove all markdown code fences (```json, ```, etc.)
        $text = preg_replace('/```(?:json)?\s*/i', '', $text);
        $text = str_replace('`', '', $text);

        $text = trim($text);

        // Try to extract JSON object or array from surrounding text
        if ($text !== '' && $text[0] !== '{' && $text[0] !== '[') {
            $extracted = $this->extractTopLevelJson($text);
            if ($extracted !== null) {
                $text = $extracted;
            }
        }

        // Fix trailing commas before closing braces/brackets (common AI issue)
        $text = preg_replace('/,\s*([}\]])/', '$1', $text);

        return $text;
    }

    /**
     * Extract the first top-level JSON object or array from text,
     * properly handling nested braces/brackets and strings.
     */
    protected function extractTopLevelJson(string $text): ?string
    {
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];
            if ($char === '{' || $char === '[') {
                $depth = 0;
                $inString = false;
                $escaped = false;
                $openChar = $char;
                $closeChar = $openChar === '{' ? '}' : ']';
                $start = $i;
                $j = $i;

                while ($j < $len) {
                    $c = $text[$j];
                    if ($escaped) {
                        $escaped = false;
                        $j++;
                        continue;
                    }
                    if ($c === '\\') {
                        $escaped = true;
                        $j++;
                        continue;
                    }
                    if ($c === '"') {
                        $inString = !$inString;
                        $j++;
                        continue;
                    }
                    if (!$inString) {
                        if ($c === $openChar) {
                            $depth++;
                        } elseif ($c === $closeChar) {
                            $depth--;
                            if ($depth === 0) {
                                return substr($text, $start, $j - $start + 1);
                            }
                        }
                    }
                    $j++;
                }
                // Hit end of string without closing - continue searching
                if ($depth > 0) {
                    $i = $j;
                }
            }
        }
        return null;
    }
}
