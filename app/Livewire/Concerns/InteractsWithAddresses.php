<?php

namespace App\Livewire\Concerns;

use App\Services\Frontend\Account\AccountService;
use Illuminate\Support\Facades\Auth;

trait InteractsWithAddresses
{
    public string $shippingAddress = '';

    public string $billingAddress = '';

    public bool $billingSameAsShipping = true;

    public function mountInteractsWithAddresses(): void
    {
        $this->shippingAddress = (string) Auth::user()->shipping_address;
        $this->billingAddress = (string) Auth::user()->billing_address;
        $this->billingSameAsShipping = $this->billingAddress === '' || $this->billingAddress === $this->shippingAddress;
    }

    public function updateAddresses(): void
    {
        $validated = $this->validate([
            'shippingAddress' => ['nullable', 'string', 'max:500'],
            'billingSameAsShipping' => ['boolean'],
            'billingAddress' => ['exclude_if:billingSameAsShipping,true', 'nullable', 'string', 'max:500'],
        ]);

        $shippingAddress = filled($validated['shippingAddress']) ? trim($validated['shippingAddress']) : null;

        app(AccountService::class)->updateAddresses(Auth::user(), [
            'shipping_address' => $shippingAddress,
            'billing_address' => $this->billingSameAsShipping ? $shippingAddress : (filled($validated['billingAddress'] ?? null) ? trim($validated['billingAddress']) : null),
        ]);

        $this->dispatch('toast-show', slots: ['text' => __('Addresses updated.')], dataset: ['variant' => 'success']);
    }
}
