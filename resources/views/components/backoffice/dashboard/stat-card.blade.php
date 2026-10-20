@props([
    'icon',
    'label',
    'value',
    'trend' => null,
    'trendUp' => true,
])

<flux:card {{ $attributes->class(['flex items-start justify-between gap-4']) }}>
    <div class="space-y-1">
        <flux:text>{{ $label }}</flux:text>
        <flux:heading size="xl">{{ $value }}</flux:heading>
        @if ($trend)
            <p @class([
                'flex items-center gap-1 text-xs font-medium',
                'text-emerald-600 dark:text-emerald-400' => $trendUp,
                'text-rose-600 dark:text-rose-400' => ! $trendUp,
            ])>
                <flux:icon :icon="$trendUp ? 'arrow-trending-up' : 'arrow-trending-down'" class="size-4" />
                {{ $trend }}
            </p>
        @endif
    </div>

    <x-ui.icon-badge size="lg">
        <flux:icon :icon="$icon" variant="outline" class="size-5" />
    </x-ui.icon-badge>
</flux:card>
