@props([
    'tone' => 'brand',
    'size' => 'md',
])

<div data-banner data-tone="{{ $tone }}" {{ $attributes->class([
    match ($size) {
        'sm' => 'rounded-md px-3 py-1.5 text-sm',
        'lg' => 'rounded-2xl p-6',
        'none' => '',
        default => 'rounded-lg px-4 py-3 text-sm',
    },
]) }}>
    {{ $slot }}
</div>
