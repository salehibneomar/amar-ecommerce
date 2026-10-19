@props([
    'bar' => false,
])

<flux:avatar {{ $attributes->class($bar ? 'bg-bar-active text-bar-active-foreground' : 'border border-tint-line bg-tint text-tint-foreground') }} />
