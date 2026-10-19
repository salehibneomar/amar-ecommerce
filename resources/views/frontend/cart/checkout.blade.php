<x-layouts::frontend :title="__('Checkout')">
    <x-frontend.layout.page :title="__('Checkout')" width="max-w-5xl">
        <x-slot:actions>
            <flux:button variant="ghost" icon="arrow-left" :href="route('shop')" wire:navigate>{{ __('Continue shopping') }}</flux:button>
        </x-slot:actions>

        <livewire:frontend.cart.checkout />
    </x-frontend.layout.page>
</x-layouts::frontend>
