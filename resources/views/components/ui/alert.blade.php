@props([
    'tone' => null,
    'variant' => null,
    'color' => null,
])

<flux:callout :data-tone="$tone ?? match (true) {
    $variant === 'success' || in_array($color, ['green', 'emerald', 'lime'], true) => 'success',
    $variant === 'warning' || in_array($color, ['amber', 'yellow', 'orange'], true) => 'warning',
    $variant === 'danger' || in_array($color, ['red', 'rose'], true) => 'danger',
    in_array($color, ['sky', 'cyan', 'blue'], true) => 'info',
    in_array($color, ['zinc', 'gray'], true) => 'neutral',
    default => 'brand',
}" {{ $attributes }}>{{ $slot }}</flux:callout>
