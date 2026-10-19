@props([
    'href',
    'current' => false,
    'variant' => 'inline',
])

<a href="{{ $href }}" @if ($current) aria-current="page" @endif {{ $attributes->class([
    'rounded-lg px-3 py-1.5 text-white transition-colors' => $variant === 'inline',
    'hover:bg-white/15' => $variant === 'inline' && ! $current,
    'bg-white/20 font-semibold' => $variant === 'inline' && $current,
    'text-brand-700 dark:text-brand-300' => $variant === 'stacked' && $current,
    'flex items-center rounded-lg px-3 py-2.5 text-base hover:bg-zinc-100 dark:hover:bg-zinc-800/70' => $variant === 'stacked',
    'bg-brand-50 font-semibold dark:bg-brand-950/60' => $variant === 'stacked' && $current,
]) }}>{{ $slot }}</a>
