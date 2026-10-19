@props([
    'amount' => 0,
    'original' => null,
    'free' => false,
    'size' => 'base',
])

<span {{ $attributes->class([
    'inline-flex items-baseline gap-1.5 whitespace-nowrap',
    'text-sm' => $size === 'sm',
    'text-base' => $size === 'base',
    'text-2xl' => $size === 'lg',
]) }}>
    @if ($free)
        <span class="font-semibold text-brand-700 dark:text-brand-300">{{ __('Free') }}</span>
    @else
        <span class="font-semibold text-zinc-900 dark:text-white">{{ formatMoney($amount) }}</span>
        @if ($original !== null && (float) $original > (float) $amount)
            <span class="text-[0.8em] text-zinc-400 line-through">{{ formatMoney($original) }}</span>
        @endif
    @endif
</span>
