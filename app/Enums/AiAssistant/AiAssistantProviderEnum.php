<?php

namespace App\Enums\AiAssistant;

enum AiAssistantProviderEnum: string
{
    case Gemini = 'gemini';

    public function label(): string
    {
        return match ($this) {
            self::Gemini => __('Gemini'),
        };
    }
}
