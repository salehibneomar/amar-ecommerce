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
            'How do I place an order?' => 'Add products to your cart, go to checkout and enter your phone number and delivery address. We confirm the order and keep you updated.',
            'How can I track my order?' => 'Open My orders in your account, or use Track order with your order ID and email.',
            'How long does delivery take?' => 'Most orders are delivered within a few working days after they ship.',
            'Can I cancel an order?' => 'Yes, before it ships. Contact us with your order ID.',
            'What if a product is out of stock?' => 'You cannot add it to the cart until it is back in stock.',
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
