@props([
    'offer',
])

<x-ui.banner tone="brand" size="none" {{ $attributes->class('flex flex-col gap-5 rounded-2xl p-5 sm:flex-row sm:items-center sm:gap-6 sm:p-6') }}>
    <div class="flex size-20 shrink-0 flex-col items-center justify-center rounded-xl border border-gold-300 bg-gold-200 text-gold-800 shadow-card dark:border-gold-500 dark:bg-gold-400 dark:text-gold-900">
        <span class="text-2xl leading-none font-bold">{{ $offer->discount_percentage }}%</span>
        <span class="mt-1 text-xs font-semibold tracking-wider uppercase">{{ __('Off') }}</span>
    </div>

    <div class="min-w-0 flex-1">
        <p class="text-base font-semibold text-brand-text">{{ $offer->name }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $offer->headline }}</p>
        @if ($offer->description)
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $offer->description }}</p>
        @endif
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-3 sm:flex-col sm:items-end">
        @if ($offer->ends_at)
            <x-frontend.content.countdown :to="$offer->ends_at" icon="schedule" class="text-sm" />
        @endif
        <x-ui.button variant="primary" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('Shop the Offer') }}</x-ui.button>
    </div>
</x-ui.banner>
