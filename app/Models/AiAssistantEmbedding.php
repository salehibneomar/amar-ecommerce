<?php

namespace App\Models;

use Database\Factories\AiAssistantEmbeddingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $embeddable_type
 * @property int $embeddable_id
 * @property string $model
 * @property string $content_hash
 * @property list<float> $vector
 */
class AiAssistantEmbedding extends Model
{
    /** @use HasFactory<AiAssistantEmbeddingFactory> */
    use HasFactory;

    protected $fillable = [
        'embeddable_type',
        'embeddable_id',
        'model',
        'content_hash',
        'vector',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vector' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }
}
