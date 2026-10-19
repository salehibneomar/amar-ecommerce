@props([
    'color' => 'zinc',
    'size' => 'sm',
    'solid' => false,
    'circle' => false,
    'overlay' => false,
])

@if ($overlay)
    <span class="inline-flex rounded-sm bg-surface shadow-sm">
@endif
<flux:badge size="sm" :color="in_array($color, ['white', 'gold'], true) ? 'zinc' : $color" :variant="$solid ? 'solid' : null" {{ $attributes->class([
    'items-center border border-current/20 font-semibold leading-none',
    'h-6 py-0!' => $size === 'sm',
    'h-5 px-1.5! py-0! text-[10px]!' => $size === 'xs',
    'w-6 justify-center rounded-full px-0!' => $circle && $size === 'sm',
    'min-w-5 justify-center rounded-full px-1!' => $circle && $size === 'xs',
    'bg-white! text-zinc-600! border-zinc-200! dark:bg-white/10! dark:text-zinc-200! dark:border-white/15!' => $color === 'white',
    'bg-gold-400! text-zinc-900! border-gold-500/40!' => $color === 'gold',
]) }}>{{ $slot }}</flux:badge>
@if ($overlay)
    </span>
@endif
