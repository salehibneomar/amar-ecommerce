@props([
    'amount' => 0,
    'original' => null,
    'free' => false,
    'size' => 'base',
    'plain' => false,
])

<span {{ $attributes->class([
    'inline-flex items-baseline gap-1.5 whitespace-nowrap',
    'text-sm' => $size === 'sm',
    'text-base' => $size === 'base',
    'text-2xl' => $size === 'lg',
]) }}>
    @if ($free)
        <span @class(['text-brand-text', 'font-semibold' => ! $plain])>{{ __('Free') }}</span>
    @else
        <span @class(['font-semibold text-zinc-900 dark:text-white' => ! $plain])>{{ formatMoney($amount) }}</span>
        @if ($original !== null && (float) $original > (float) $amount)
            <span class="text-[0.8em] text-zinc-400 line-through">{{ formatMoney($original) }}</span>
        @endif
    @endif
</span>
