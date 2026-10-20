<?php

namespace Database\Seeders;

use App\Enums\Catalog\ProductBadgeEnum;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Smartphone X1', 'Phones', 24500, ProductBadgeEnum::Hot],
            ['Ultrabook 14', 'Laptops', 78000, ProductBadgeEnum::New],
            ['Wireless Earbuds', 'Accessories', 2900, ProductBadgeEnum::Sale],
            ['Classic Cotton Shirt', 'Men', 1450, null],
            ['Everyday Kurti', 'Women', 1800, ProductBadgeEnum::New],
            ['Kids Hoodie', 'Kids', 1200, ProductBadgeEnum::Sale],
            ['Non-stick Fry Pan', 'Kitchen', 1650, null],
            ['Ceramic Table Lamp', 'Decor', 2300, ProductBadgeEnum::Hot],
            ['Daily Face Wash', 'Skin Care', 450, null],
            ['Electric Trimmer', 'Personal Care', 2100, null],
        ];

        foreach ($products as $index => [$name, $categoryName, $price, $badge]) {
            Product::factory()->create([
                'name' => $name,
                'category_id' => Category::query()->where('name', $categoryName)->value('id'),
                'price' => $price,
                'discount_percentage' => $badge === ProductBadgeEnum::Sale ? 15 : null,
                'quantity' => 25,
                'badge' => $badge,
                'is_featured' => $index < 6,
            ]);
        }
    }
}
