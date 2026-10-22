<?php

use App\Models\Product;
use App\Services\AiAssistant\AiAssistantGeminiService;
use App\Services\AiAssistant\AiAssistantOpenAiCompatibleService;

return [
    'enabled' => (bool) env('AI_ASSISTANT_ENABLED', false),
    'history_messages' => (int) env('AI_ASSISTANT_HISTORY_MESSAGES', 12),
    'message_max_length' => (int) env('AI_ASSISTANT_MESSAGE_MAX_LENGTH', 500),
    'timeout_seconds' => (int) env('AI_ASSISTANT_TIMEOUT_SECONDS', 20),
    'top_matches' => (int) env('AI_ASSISTANT_TOP_MATCHES', 5),
    'results' => (int) env('AI_ASSISTANT_RESULTS', 5),
    'cache_minutes' => (int) env('AI_ASSISTANT_CACHE_MINUTES', 60),
    'max_output_tokens' => (int) env('AI_ASSISTANT_MAX_OUTPUT_TOKENS', 1024),
    'example_prompts' => array_values(array_filter(array_map('trim', explode('|', (string) env(
        'AI_ASSISTANT_EXAMPLE_PROMPTS_UI',
        'Suggest a gift under 1000|What is new this week?|Which products are on sale?',
    ))))),
    'models' => [
        Product::class,
    ],
    'drivers' => [
        'gemini' => AiAssistantGeminiService::class,
        'openai' => AiAssistantOpenAiCompatibleService::class,
    ],
    'chat' => [
        'driver' => env('AI_CHAT_DRIVER', 'openai'),
        'url' => env('AI_CHAT_URL'),
        'api_key' => env('AI_CHAT_API_KEY'),
        'model' => env('AI_CHAT_MODEL'),
        'reasoning' => env('AI_CHAT_REASONING'),
    ],
    'embedding' => [
        'driver' => env('AI_EMBEDDING_DRIVER') ?: env('AI_CHAT_DRIVER', 'openai'),
        'url' => env('AI_EMBEDDING_URL') ?: env('AI_CHAT_URL'),
        'api_key' => env('AI_EMBEDDING_API_KEY') ?: env('AI_CHAT_API_KEY'),
        'model' => env('AI_EMBEDDING_MODEL'),
        'dimensions' => env('AI_EMBEDDING_DIMENSIONS'),
    ],
];
