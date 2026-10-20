@props([
    'variant' => 'outline',
    'color' => null,
    'bar' => false,
])

@isset($iconLeading)
    <flux:button
        :variant="$variant"
        :data-tone="$variant === 'primary' ? 'brand' : ($variant === 'danger' ? 'danger' : (in_array($variant, ['ghost', 'subtle'], true) ? $color : ($color ?: 'neutral')))"
        :data-ghost="in_array($variant, ['ghost', 'subtle'], true) && $color ? 'true' : null"
        :data-bar="$bar ? 'true' : null"
        {{ $attributes->merge(['icon:variant' => 'outline']) }}
    >
        <x-slot:iconLeading>{{ $iconLeading }}</x-slot:iconLeading>
        {{ $slot }}
    </flux:button>
@else
    <flux:button
        :variant="$variant"
        :data-tone="$variant === 'primary' ? 'brand' : ($variant === 'danger' ? 'danger' : (in_array($variant, ['ghost', 'subtle'], true) ? $color : ($color ?: 'neutral')))"
        :data-ghost="in_array($variant, ['ghost', 'subtle'], true) && $color ? 'true' : null"
        :data-bar="$bar ? 'true' : null"
        {{ $attributes->merge(['icon:variant' => 'outline']) }}
    >{{ $slot }}</flux:button>
@endisset
