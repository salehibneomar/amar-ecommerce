<form wire:submit="updateAddresses" {{ $attributes->class('space-y-6') }}>
    <flux:textarea wire:model="shippingAddress" :label="__('Shipping Address')" rows="3" :placeholder="__('House, road, area and city')" autocomplete="shipping street-address" />

    <flux:checkbox wire:model="billingSameAsShipping" :label="__('Billing Address Same as Shipping')" />

    <div x-show="! $wire.billingSameAsShipping" x-cloak>
        <flux:textarea wire:model="billingAddress" :label="__('Billing Address')" rows="3" :placeholder="__('House, road, area and city')" autocomplete="billing street-address" />
    </div>

    <x-form.actions>
        <x-ui.button variant="primary" type="submit">{{ __('Save') }}</x-ui.button>
    </x-form.actions>
</form>
