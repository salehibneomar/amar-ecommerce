<?php

namespace App\Interfaces\AiAssistant;

use App\Enums\AiAssistant\AiAssistantRoleEnum;

interface AiAssistantProviderInterface
{
    /**
     * @return list<float>|null
     */
    public function embed(string $text): ?array;

    /**
     * @param  list<array{role: AiAssistantRoleEnum, text: string}>  $messages
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(string $system, array $messages, array $schema): array;
}
