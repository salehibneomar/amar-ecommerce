@props([
    'color' => 'zinc',
    'solid' => false,
])

<flux:badge size="sm" :color="$color" :variant="$solid ? 'solid' : null" {{ $attributes->class('font-semibold') }}>{{ $slot }}</flux:badge>
