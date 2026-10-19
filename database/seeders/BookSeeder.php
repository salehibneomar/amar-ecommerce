<?php

namespace Database\Seeders;

use App\Enums\Catalog\BookTypeEnum;
use App\Enums\Catalog\ProductBadgeEnum;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            ['আধুনিক ধান চাষ পদ্ধতি', 'ড. মোঃ রফিকুল ইসলাম', 'ধান ও শস্য', 350, ProductBadgeEnum::Hot],
            ['জৈব সার তৈরি ও ব্যবহার', 'নাসরিন আক্তার', 'ধান ও শস্য', 220, null],
            ['ছাদ বাগানে সবজি চাষ', 'কামরুল হাসান', 'সবজি চাষ', 280, ProductBadgeEnum::New],
            ['লাভজনক আম চাষ', 'আব্দুল করিম', 'ফল চাষ', 300, ProductBadgeEnum::Sale],
            ['গরু মোটাতাজাকরণ নির্দেশিকা', 'ডা. শফিকুল আলম', 'গবাদি পশু', 400, ProductBadgeEnum::Hot],
            ['দেশি মুরগি পালন', 'সালমা বেগম', 'হাঁস-মুরগি', 250, null],
            ['পুকুরে মাছ চাষের সহজ পাঠ', 'জাহিদ হোসেন', 'মৎস্য চাষ', 320, ProductBadgeEnum::New],
            ['মাটির গান', 'তাহমিনা রহমান', 'উপন্যাস', 450, null],
            ['গ্রামের পথে', 'মাহবুব আলম', 'ছোটগল্প', 200, null],
            ['সোনামণিদের ছড়া', 'রুবিনা ইয়াসমিন', 'ছড়া ও কবিতা', 150, ProductBadgeEnum::Sale],
        ];

        $bookType = BookTypeEnum::current();

        foreach ($books as $index => [$name, $author, $categoryName, $price, $badge]) {
            Book::factory()
                ->state([
                    'name' => $name,
                    'author' => $author,
                    'pages' => fake()->numberBetween(80, 320),
                    'category_id' => Category::query()->where('name', $categoryName)->value('id'),
                    'price' => $price,
                    'discount_percentage' => $badge === ProductBadgeEnum::Sale ? 15 : null,
                    'badge' => $badge,
                    'is_featured' => $index < 6,
                ])
                ->when($bookType->isPhysicalOnly() || ($bookType === BookTypeEnum::Both && $index === 4), fn ($factory) => $factory->physical(25))
                ->create();
        }

        if ($bookType->isPhysicalOnly()) {
            return;
        }

        Book::factory()->free()->create([
            'name' => 'কৃষি তথ্য পুস্তিকা',
            'author' => 'নতুন কৃষক',
            'category_id' => Category::query()->where('name', 'ধান ও শস্য')->value('id'),
        ]);
    }
}
