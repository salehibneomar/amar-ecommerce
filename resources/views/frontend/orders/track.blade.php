<x-layouts::frontend :title="__('Track order')">
    <x-frontend.layout.page :title="__('Track your order')" :subtitle="__('Enter your order ID and the email you used when ordering.')">
        <livewire:frontend.orders.track />
    </x-frontend.layout.page>
</x-layouts::frontend>
