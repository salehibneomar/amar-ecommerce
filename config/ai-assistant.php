<?php

use App\Models\Book;
use App\Services\AiAssistant\AiAssistantGeminiService;

return [
    'enabled' => (bool) env('AI_ASSISTANT_ENABLED', false),
    'provider' => env('AI_ASSISTANT_PROVIDER', 'gemini'),
    'history_messages' => (int) env('AI_ASSISTANT_HISTORY_MESSAGES', 12),
    'message_max_length' => (int) env('AI_ASSISTANT_MESSAGE_MAX_LENGTH', 500),
    'timeout_seconds' => (int) env('AI_ASSISTANT_TIMEOUT_SECONDS', 20),
    'candidates' => (int) env('AI_ASSISTANT_CANDIDATES', 20),
    'results' => (int) env('AI_ASSISTANT_RESULTS', 5),
    'cache_minutes' => (int) env('AI_ASSISTANT_CACHE_MINUTES', 60),
    'max_output_tokens' => (int) env('AI_ASSISTANT_MAX_OUTPUT_TOKENS', 1024),
    'example_prompts' => array_values(array_filter(array_map('trim', explode('|', (string) env(
        'AI_ASSISTANT_EXAMPLE_PROMPTS_UI',
        'Suggest a book on rice farming|Which books help with fish farming?|Do you sell printed books or only PDFs?',
    ))))),
    'models' => [
        Book::class,
    ],
    'providers' => [
        'gemini' => [
            'class' => AiAssistantGeminiService::class,
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL'),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL'),
            'embedding_dimensions' => (int) env('GEMINI_EMBEDDING_DIMENSIONS', 768),
            'thinking_budget' => env('GEMINI_THINKING_BUDGET'),
        ],
    ],
];
