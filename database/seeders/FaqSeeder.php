<?php

namespace Database\Seeders;

use App\Enums\Content\FaqStatusEnum;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            'How do I buy a book?' => 'Add the book to your cart, go to checkout, pay with bKash or Nagad and enter the transaction ID. We verify it and confirm your order.',
            'When can I read a digital book?' => 'As soon as we verify your payment. The book then appears in My library and opens in your browser.',
            'Can I read before buying?' => 'Yes. Every paid book has a free preview of the first few pages.',
            'Do you deliver printed books?' => 'Yes. Printed books are delivered to the address you enter at checkout.',
            'Can I read books on my phone?' => 'Yes. Books open in the browser on any device.',
        ];

        foreach ($faqs as $title => $description) {
            Faq::create([
                'title' => $title,
                'description' => $description,
                'status' => FaqStatusEnum::Active,
            ]);
        }
    }
}
