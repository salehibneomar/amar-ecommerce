@props([
    'size' => 'md',
    'tone' => 'brand',
    'circle' => false,
])

<span @if (! in_array($tone, ['neutral', 'success', 'warning', 'danger'], true)) data-tone="brand" @endif {{ $attributes->class([
    'icon-badge flex shrink-0 items-center justify-center',
    match ($size) {
        'xs' => 'size-8',
        'sm' => 'size-9',
        'lg' => 'size-11',
        'xl' => 'size-14',
        '2xl' => 'size-16',
        default => 'size-10',
    },
    $circle ? 'rounded-full' : match ($size) {
        'xs', 'sm' => 'rounded-md',
        'xl', '2xl' => 'rounded-xl',
        default => 'rounded-lg',
    },
    match ($tone) {
        'neutral' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'success' => 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'danger' => 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-300',
        default => '',
    },
]) }}>
    {{ $slot }}
</span>
