<?php

namespace Database\Seeders;

use App\Enums\Content\OfferTypeEnum;
use App\Models\Category;
use App\Models\Offer;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        Offer::factory()->create([
            'name' => 'Opening Offer',
            'headline' => '10% off everything',
            'description' => 'Order today to get the offer',
            'discount_percentage' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        if (isGlobalOfferOnly()) {
            return;
        }

        Offer::factory()->create([
            'name' => 'Fashion Week',
            'headline' => '20% off fashion',
            'type' => OfferTypeEnum::Specific,
            'description' => 'Order today to get the offer',
            'discount_percentage' => 20,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ])->categories()->attach(Category::query()->where('name', 'Fashion')->value('id'));
    }
}
