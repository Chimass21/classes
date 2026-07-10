<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('OPENAI_MODEL', 'deepseek-chat'),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'timeout' => env('OPENAI_TIMEOUT', 120),
    ],
];
