<?php

namespace Database\Factories;

use App\Models\AiAssistantEmbedding;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiAssistantEmbedding>
 */
class AiAssistantEmbeddingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'embeddable_type' => (new Product)->getMorphClass(),
            'embeddable_id' => Product::factory(),
            'model' => 'test-embedding-model',
            'content_hash' => fake()->sha256(),
            'vector' => [1.0, 0.0, 0.0],
        ];
    }
}
