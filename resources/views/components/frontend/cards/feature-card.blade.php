@props([
    'icon',
    'title',
    'description',
])

<x-frontend.cards.card interactive :padded="false" {{ $attributes->class('p-6') }}>
    <x-ui.icon-badge size="lg">
        <span class="material-symbols-outlined">{{ $icon }}</span>
    </x-ui.icon-badge>
    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $description }}</p>
</x-frontend.cards.card>
