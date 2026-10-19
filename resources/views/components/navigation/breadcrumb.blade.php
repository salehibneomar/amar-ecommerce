@props([
    'items' => [],
])

<flux:breadcrumbs {{ $attributes }}>
    @foreach ($items as $item)
        <flux:breadcrumbs.item :href="$item['href'] ?? null" wire:navigate>
            {{ $item['label'] }}
        </flux:breadcrumbs.item>
    @endforeach
</flux:breadcrumbs>
