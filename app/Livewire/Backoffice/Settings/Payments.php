<?php

namespace App\Livewire\Backoffice\Settings;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Services\Backoffice\Payment\PaymentGatewaySettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read array<string, array{label: string, description: string, is_configured: bool}> $gatewayDetails
 */
class Payments extends Component
{
    /**
     * @var list<array{gateway: string, is_enabled: bool}>
     */
    public array $gateways = [];

    public string $bkashNumber = '';

    public string $nagadNumber = '';

    protected PaymentGatewaySettingService $gatewaySettingService;

    public function boot(PaymentGatewaySettingService $gatewaySettingService): void
    {
        $this->gatewaySettingService = $gatewaySettingService;
    }

    public function mount(): void
    {
        $this->gateways = $this->gatewaySettingService->gateways();

        $manual = $this->gatewaySettingService->manualSettings();
        $this->bkashNumber = (string) $manual['bkash_number'];
        $this->nagadNumber = (string) $manual['nagad_number'];
    }

    public function move(int $index, int $offset): void
    {
        $target = $index + $offset;

        if (! isset($this->gateways[$index], $this->gateways[$target]) || abs($offset) !== 1 || ! ($this->gatewayDetails[$this->gateways[$index]['gateway']]['is_configured'] ?? false)) {
            return;
        }

        [$this->gateways[$index], $this->gateways[$target]] = [$this->gateways[$target], $this->gateways[$index]];
    }

    public function saveGateways(): void
    {
        $available = array_map(fn (PaymentGatewayEnum $gateway): string => $gateway->value, PaymentGatewayEnum::available());

        $validated = $this->validate([
            'gateways' => ['required', 'array', 'list', 'size:'.count($available)],
            'gateways.*.gateway' => ['required', 'string', 'distinct', Rule::in($available)],
            'gateways.*.is_enabled' => ['required', 'boolean'],
        ]);

        $gateways = array_values(array_map(fn (array $row): array => ['gateway' => PaymentGatewayEnum::from($row['gateway']), 'is_enabled' => (bool) $row['is_enabled']], $validated['gateways']));
        $errors = [];

        foreach ($gateways as $index => $row) {
            if ($row['is_enabled'] && ! $this->gatewayDetails[$row['gateway']->value]['is_configured']) {
                $errors["gateways.{$index}.is_enabled"] = __(':gateway is not installed, so it cannot be turned on.', ['gateway' => $row['gateway']->label()]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->gatewaySettingService->save($gateways);

        $this->dispatch('toast-show', slots: ['text' => __('Payment gateways saved.')], dataset: ['variant' => 'success']);
    }

    public function saveManualNumbers(): void
    {
        $validated = $this->validate([
            'bkashNumber' => ['nullable', 'digits:'.validPhoneLength()],
            'nagadNumber' => ['nullable', 'digits:'.validPhoneLength()],
        ]);

        $this->gatewaySettingService->saveManualNumbers(
            $validated['bkashNumber'] ?: null,
            $validated['nagadNumber'] ?: null,
        );

        $this->dispatch('toast-show', slots: ['text' => __('Manual payment numbers saved.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return array<string, array{label: string, description: string, is_configured: bool}>
     */
    #[Computed]
    public function gatewayDetails(): array
    {
        return array_column(array_map(fn (PaymentGatewayEnum $gateway): array => [$gateway->value, [
            'label' => $gateway->label(),
            'description' => $gateway->description(),
            'is_configured' => $gateway->isConfigured(),
        ]], PaymentGatewayEnum::available()), 1, 0);
    }

    public function render(): View
    {
        return view('components.backoffice.settings.payments');
    }
}
