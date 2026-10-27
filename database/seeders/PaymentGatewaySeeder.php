<?php

namespace Database\Seeders;

use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $enabled = array_values(array_unique(paymentGateways()));

        foreach (PaymentGatewayEnum::cases() as $index => $gateway) {
            $position = array_search($gateway->value, $enabled, true);

            PaymentGateway::query()->firstOrCreate(['gateway' => $gateway], [
                'is_enabled' => $position !== false,
                'sort_order' => $position === false ? count($enabled) + $index : $position,
                'settings' => $gateway->isManual() ? [
                    'bkash_number' => paymentAccountNumber(PaymentMethodEnum::Bkash->value),
                    'nagad_number' => paymentAccountNumber(PaymentMethodEnum::Nagad->value),
                ] : null,
            ]);
        }
    }
}
