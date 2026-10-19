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
            'name' => 'উদ্বোধনী অফার',
            'headline' => 'সব বইয়ে ১০% ছাড়',
            'description' => 'অফার পেতে আজই অর্ডার করুন',
            'discount_percentage' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        if (isGlobalOfferOnly()) {
            return;
        }

        Offer::factory()->create([
            'name' => 'শিশু-কিশোর উৎসব',
            'headline' => 'শিশু-কিশোর বইয়ে ২০% ছাড়',
            'type' => OfferTypeEnum::Specific,
            'description' => 'অফার পেতে আজই অর্ডার করুন',
            'discount_percentage' => 20,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ])->categories()->attach(Category::query()->where('name', 'শিশু-কিশোর')->value('id'));
    }
}
