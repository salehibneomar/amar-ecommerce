<?php

use App\Services\Payment\CodPaymentGatewayService;
use App\Services\Payment\ManualPaymentGatewayService;
use App\Services\Payment\StripePaymentGatewayService;

return [
    'enabled' => array_values(array_filter(array_map('trim', explode(',', (string) env('PAYMENT_GATEWAYS', ''))))),

    'gateways' => [
        'cod' => CodPaymentGatewayService::class,
        'manual' => ManualPaymentGatewayService::class,
        'stripe' => StripePaymentGatewayService::class,
    ],

    'manual' => [
        'default_method' => env('PAYMENT_DEFAULT_METHOD', 'bkash'),
        'numbers' => [
            'bkash' => env('PAYMENT_BKASH_NUMBER'),
            'nagad' => env('PAYMENT_NAGAD_NUMBER'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'api_url' => 'https://api.stripe.com/v1',
        'webhook_tolerance' => 300,
    ],
];
