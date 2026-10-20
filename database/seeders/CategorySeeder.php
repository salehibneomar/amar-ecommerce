<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Electronics' => ['Phones', 'Laptops', 'Accessories'],
            'Fashion' => ['Men', 'Women', 'Kids'],
            'Home and Living' => ['Kitchen', 'Decor'],
            'Health and Beauty' => ['Skin Care', 'Personal Care'],
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
