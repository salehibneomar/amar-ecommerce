@props([
    'href',
    'current' => false,
    'variant' => 'inline',
])

<a href="{{ $href }}" @if ($current) aria-current="page" @endif {{ $attributes->class([
    'rounded-lg border border-transparent px-3 py-1.5 transition-colors' => $variant === 'inline',
    'text-bar-foreground hover:bg-bar-hover hover:text-bar-active-foreground' => $variant === 'inline' && ! $current,
    'border-bar-active-line bg-bar-active font-semibold text-bar-active-foreground' => $variant === 'inline' && $current,
    'text-brand-text' => $variant === 'stacked' && $current,
    'flex items-center rounded-lg px-3 py-2.5 text-base transition-colors' => $variant === 'stacked',
    'hover:bg-menu-hover hover:text-bar-active-foreground' => $variant === 'stacked' && ! $current,
    'bg-brand-50 font-semibold dark:bg-brand-950/60' => $variant === 'stacked' && $current,
]) }}>{{ $slot }}</a>
