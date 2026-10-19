@props([
    'icon',
    'title',
    'value',
    'href',
    'action',
])

<x-frontend.cards.card interactive :padded="false" {{ $attributes->class('flex flex-col p-6') }}>
    <x-ui.icon-badge size="lg">
        <span class="material-symbols-outlined">{{ $icon }}</span>
    </x-ui.icon-badge>
    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-1 text-sm break-all text-zinc-600 dark:text-zinc-400">{{ $value }}</p>
    <div class="mt-auto pt-5">
        <x-ui.button size="sm" variant="outline" :href="$href" target="_blank" rel="noopener">{{ $action }}</x-ui.button>
    </div>
</x-frontend.cards.card>
