<x-layouts::frontend :title="__('Checkout')">
    <x-frontend.layout.page :title="__('Checkout')">
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('shop')" wire:navigate>{{ __('Continue Shopping') }}</x-ui.button>
        </x-slot:actions>

        <livewire:frontend.cart.checkout />
    </x-frontend.layout.page>
</x-layouts::frontend>
