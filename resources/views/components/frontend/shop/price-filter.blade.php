<flux:field {{ $attributes }}>
    <flux:label>{{ __('Price (:symbol)', ['symbol' => appCurrencySymbol()]) }}</flux:label>
    <div class="grid grid-cols-2 gap-2">
        <flux:input wire:model.live.debounce.500ms="minPrice" type="number" min="0" inputmode="numeric" :placeholder="__('Min')" :aria-label="__('Minimum price')" />
        <flux:input wire:model.live.debounce.500ms="maxPrice" type="number" min="0" inputmode="numeric" :placeholder="__('Max')" :aria-label="__('Maximum price')" />
    </div>
</flux:field>
