<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('OPENAI_MODEL', 'deepseek-chat'),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'timeout' => env('OPENAI_TIMEOUT', 120),
    ],

    'whatsapp' => [
        // Number exam results are forwarded to via wa.me deep links.
        // Accepts local (0806...) or international (+2348062...) formats.
        'phone' => env('WHATSAPP_PHONE', '+2348062078597'),
    ],
];
