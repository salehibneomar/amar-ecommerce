<?php

namespace App\Services\Backoffice\Payment;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\PaymentGateway;
use App\Traits\Cache\FrontendCacheTrait;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySettingService
{
    use FrontendCacheTrait;

    /**
     * @var array<int, array{gateway: string, is_enabled: bool, settings: array<string, string|null>}>|null
     */
    private ?array $rows = null;

    /**
     * @return list<array{gateway: string, is_enabled: bool}>
     */
    public function gateways(): array
    {
        $saved = array_column($this->rows(), 'is_enabled', 'gateway');
        $gateways = array_map(fn (PaymentGatewayEnum $gateway): array => ['gateway' => $gateway->value, 'is_enabled' => $saved[$gateway->value] ?? false], PaymentGatewayEnum::available());
        $order = array_flip(array_keys($saved));

        usort($gateways, fn (array $a, array $b): int => ($order[$a['gateway']] ?? PHP_INT_MAX) <=> ($order[$b['gateway']] ?? PHP_INT_MAX));

        return $gateways;
    }

    /**
     * @return list<PaymentGatewayEnum>
     */
    public function enabledGateways(): array
    {
        $available = PaymentGatewayEnum::available();
        $enabled = [];

        foreach ($this->rows() as $row) {
            $gateway = PaymentGatewayEnum::tryFrom($row['gateway']);

            if ($row['is_enabled'] && $gateway !== null && in_array($gateway, $available, true)) {
                $enabled[] = $gateway;
            }
        }

        return $enabled;
    }

    /**
     * @return array{bkash_number: string|null, nagad_number: string|null}
     */
    public function manualSettings(): array
    {
        $manual = array_column($this->rows(), 'settings', 'gateway')[PaymentGatewayEnum::Manual->value] ?? [];

        return [
            'bkash_number' => $manual['bkash_number'] ?? null,
            'nagad_number' => $manual['nagad_number'] ?? null,
        ];
    }

    /**
     * @param  list<array{gateway: PaymentGatewayEnum, is_enabled: bool}>  $gateways
     */
    public function save(array $gateways): void
    {
        DB::transaction(function () use ($gateways): void {
            foreach ($gateways as $sortOrder => $gateway) {
                PaymentGateway::query()->updateOrCreate(['gateway' => $gateway['gateway']], ['is_enabled' => $gateway['is_enabled'], 'sort_order' => $sortOrder]);
            }
        });

        $this->rows = null;
    }

    public function saveManualNumbers(?string $bkashNumber, ?string $nagadNumber): void
    {
        PaymentGateway::query()->updateOrCreate(['gateway' => PaymentGatewayEnum::Manual], [
            'settings' => ['bkash_number' => $bkashNumber, 'nagad_number' => $nagadNumber],
        ]);

        $this->rows = null;
    }

    /**
     * @return array<int, array{gateway: string, is_enabled: bool, settings: array<string, string|null>}>
     */
    private function rows(): array
    {
        return $this->rows ??= $this->rememberFrontendCache(FrontendCacheKeyEnum::PaymentGateways, fn (): array => PaymentGateway::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['gateway', 'is_enabled', 'settings'])
            ->map(fn (PaymentGateway $gateway): array => [
                'gateway' => (string) $gateway->getRawOriginal('gateway'),
                'is_enabled' => $gateway->is_enabled,
                'settings' => (array) $gateway->settings,
            ])
            ->values()
            ->all());
    }
}
