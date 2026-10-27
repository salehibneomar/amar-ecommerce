<?php

use App\Services\Payment\Gateway\BkashPaymentGatewayService;
use App\Services\Payment\Gateway\CodPaymentGatewayService;
use App\Services\Payment\Gateway\ManualPaymentGatewayService;
use App\Services\Payment\Gateway\SslCommerzPaymentGatewayService;
use App\Services\Payment\Gateway\StripePaymentGatewayService;

return [
    'enabled' => array_values(array_filter(array_map('trim', explode(',', (string) env('PAYMENT_GATEWAYS', ''))))),

    'gateways' => [
        'cod' => CodPaymentGatewayService::class,
        'manual' => ManualPaymentGatewayService::class,
        'stripe' => StripePaymentGatewayService::class,
        'sslcommerz' => SslCommerzPaymentGatewayService::class,
        'bkash' => BkashPaymentGatewayService::class,
    ],

    'manual' => [
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

    'sslcommerz' => [
        'store_id' => env('SSLCOMMERZ_STORE_ID'),
        'store_password' => env('SSLCOMMERZ_STORE_PASSWORD'),
        'country' => env('SSLCOMMERZ_COUNTRY', 'Bangladesh'),
        'api_url' => env('SSLCOMMERZ_SANDBOX', true) ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com',
    ],

    'bkash' => [
        'app_key' => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username' => env('BKASH_USERNAME'),
        'password' => env('BKASH_PASSWORD'),
        'api_url' => env('BKASH_SANDBOX', true) ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta' : 'https://tokenized.pay.bka.sh/v1.2.0-beta',
    ],
];
