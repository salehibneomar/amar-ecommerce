<?php

namespace App\Interfaces\AiAssistant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface AiAssistantInterface
{
    /**
     * @return array<string, scalar|null>
     */
    public function aiAssistantDocument(): array;

    public function aiAssistantEmbeddingText(): string;

    /**
     * @return array<string, int|float|null>
     */
    public function aiAssistantFilterValues(): array;

    /**
     * @return Builder<Model>
     */
    public static function aiAssistantEmbeddingQuery(): Builder;
}
