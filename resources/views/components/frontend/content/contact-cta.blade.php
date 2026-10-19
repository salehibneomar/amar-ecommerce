@props([
    'title',
    'description',
])

<x-ui.banner tone="brand" size="lg" {{ $attributes->class('flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center') }}>
    <div>
        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $description }}</p>
    </div>
    <x-ui.button :href="route('contact')" wire:navigate>{{ __('Contact Us') }}</x-ui.button>
</x-ui.banner>
