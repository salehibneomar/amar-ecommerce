<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'কৃষি ও চাষাবাদ' => ['ধান ও শস্য', 'সবজি চাষ', 'ফল চাষ'],
            'প্রাণিসম্পদ' => ['গবাদি পশু', 'হাঁস-মুরগি', 'মৎস্য চাষ'],
            'উপন্যাস ও গল্প' => ['উপন্যাস', 'ছোটগল্প'],
            'শিশু-কিশোর' => ['ছড়া ও কবিতা', 'রূপকথা'],
        ];

        $order = 1;

        foreach ($tree as $parentName => $children) {
            $parent = Category::factory()->featured()->create([
                'name' => $parentName,
                'sort_order' => $order++,
            ]);

            foreach ($children as $index => $childName) {
                Category::factory()->childOf($parent)->create([
                    'name' => $childName,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }
}
