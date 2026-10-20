<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            ['Everything you need in one place', 'Electronics, fashion and home essentials at fair prices'],
            ['Fast delivery to your door', 'Order today and track every step until it arrives'],
            ['New arrivals every week', 'Fresh products join the shop all the time'],
        ];

        foreach ($banners as $index => [$title, $subtitle]) {
            Banner::factory()->create([
                'title' => $title,
                'subtitle' => $subtitle,
                'link' => '/shop',
                'sort_order' => $index + 1,
            ]);
        }
    }
}
