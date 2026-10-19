@props([
    'icon',
    'label',
    'value',
    'trend' => null,
    'trendUp' => true,
])

<flux:card {{ $attributes->class(['shadow-card flex items-start justify-between gap-4 border-line bg-surface']) }}>
    <div class="space-y-1">
        <flux:text>{{ $label }}</flux:text>
        <flux:heading size="xl">{{ $value }}</flux:heading>
        @if ($trend)
            <p @class([
                'flex items-center gap-1 text-xs font-medium',
                'text-emerald-600 dark:text-emerald-400' => $trendUp,
                'text-rose-600 dark:text-rose-400' => ! $trendUp,
            ])>
                <flux:icon :icon="$trendUp ? 'arrow-trending-up' : 'arrow-trending-down'" variant="micro" />
                {{ $trend }}
            </p>
        @endif
    </div>

    <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
        <flux:icon :icon="$icon" variant="outline" class="size-5" />
    </div>
</flux:card>
