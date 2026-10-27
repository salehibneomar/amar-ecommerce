<?php

use App\Models\Faq;

test('the about and legal pages open for guests', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['about', 'terms', 'privacy', 'refund-policy']);

test('the faq page shows only active faqs', function () {
    Faq::factory()->create(['title' => 'How do I pay?']);
    Faq::factory()->inactive()->create(['title' => 'Old shipping question']);

    $this->get(route('faq'))
        ->assertOk()
        ->assertSee('How do I pay?')
        ->assertDontSee('Old shipping question');
});

test('the contact page shows only the configured channels', function () {
    config([
        'shop.support_email' => 'help@example.com',
        'shop.whatsapp_number' => '',
        'shop.facebook_page_url' => '',
        'shop.facebook_group_url' => '',
    ]);

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('mailto:help@example.com', false)
        ->assertDontSee('https://wa.me/', false);
});
