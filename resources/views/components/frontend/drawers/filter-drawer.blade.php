@props([
    'categories',
    'hasMore' => false,
    'hasFilters' => false,
])

<x-ui.drawer name="filters" side="start" static :label="__('Filters')">
    <x-slot:header>
        <flux:heading size="lg">{{ __('Filters') }}</flux:heading>
        <x-ui.button variant="ghost" size="sm" bar icon="x-mark" x-on:click="open = false" :aria-label="__('Close filters')" />
    </x-slot:header>

    <x-frontend.shop.price-filter class="mb-6" />

    <x-frontend.shop.category-filter :categories="$categories" :has-more="$hasMore" />

    <x-slot:footer>
        <div class="grid grid-cols-2 gap-2">
            <x-ui.button wire:click="clearFilters" :disabled="! $hasFilters">{{ __('Clear') }}</x-ui.button>
            <x-ui.button variant="primary" x-on:click="open = false">{{ __('Show Results') }}</x-ui.button>
        </div>
    </x-slot:footer>
</x-ui.drawer>
