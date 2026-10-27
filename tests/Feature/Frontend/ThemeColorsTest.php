<?php

use Illuminate\Mail\Markdown;

test('pages take their brand colors from the theme config', function () {
    config(['theme.presets.default.palette.600' => '#ff0055', 'theme.presets.default.palette.500' => '#ff3377']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('--theme-brand-600: #ff0055;', false)
        ->assertSee('--theme-brand: #ff0055;', false)
        ->assertSee('--theme-brand-dark: #ff3377;', false)
        ->assertSee('<meta name="theme-color" content="#ff0055" />', false);
});

test('emails take their brand colors from the theme config', function () {
    config(['theme.presets.default.palette.600' => '#ff0055']);

    $html = (string) app(Markdown::class)->render('mail::button', ['url' => 'https://example.com', 'slot' => 'Open', 'color' => 'primary']);

    expect($html)->toContain('#ff0055');
});
