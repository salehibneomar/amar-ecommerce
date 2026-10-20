@props([
    'href',
    'current' => false,
    'variant' => 'inline',
])

<a href="{{ $href }}" @if ($current) aria-current="page" @endif {{ $attributes->class([
    'rounded-lg border border-transparent transition-colors',
    'px-3 py-1.5' => $variant === 'inline',
    'text-bar-foreground hover:bg-bar-hover hover:text-bar-active-foreground' => $variant === 'inline' && ! $current,
    'border-bar-active-line bg-bar-active font-semibold text-bar-active-foreground' => $current,
    'flex items-center px-3 py-2.5 text-base' => $variant === 'stacked',
    'hover:bg-menu-hover hover:text-bar-active-foreground' => $variant === 'stacked' && ! $current,
]) }}>{{ $slot }}</a>
