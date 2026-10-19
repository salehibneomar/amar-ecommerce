<?php

use App\Services\Payment\ManualPaymentGatewayService;

return [
    'default_method' => env('PAYMENT_DEFAULT_METHOD', 'bkash'),
    'numbers' => [
        'bkash' => env('PAYMENT_BKASH_NUMBER'),
        'nagad' => env('PAYMENT_NAGAD_NUMBER'),
    ],
    'gateways' => [
        'bkash' => ManualPaymentGatewayService::class,
        'nagad' => ManualPaymentGatewayService::class,
    ],
];
