<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            ['কৃষকের হাতে জ্ঞানের আলো', 'কৃষি, প্রাণিসম্পদ ও মৎস্য চাষের সেরা বইগুলো এক জায়গায়'],
            ['ডিজিটাল বই, যেকোনো সময়', 'কিনুন আর সাথে সাথে পড়া শুরু করুন, মোবাইল বা কম্পিউটারে'],
            ['নতুন বই প্রতি মাসে', 'আমাদের সংগ্রহে প্রতিনিয়ত যোগ হচ্ছে নতুন বই'],
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
