<?php

namespace Database\Factories;

use App\Models\Book;

/**
 * @extends ProductFactory<Book>
 */
class BookFactory extends ProductFactory
{
    protected $model = Book::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...parent::definition(),
            'author' => fake()->name(),
            'pages' => fake()->optional()->numberBetween(50, 800),
        ];
    }
}
