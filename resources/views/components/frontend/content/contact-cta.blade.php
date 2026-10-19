@props([
    'title',
    'description',
])

<div {{ $attributes->class(['flex flex-col items-start justify-between gap-4 rounded-2xl bg-brand-100/60 p-6 sm:flex-row sm:items-center dark:bg-brand-900/60']) }}>
    <div>
        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $description }}</p>
    </div>
    <flux:button :href="route('contact')" wire:navigate>{{ __('Contact us') }}</flux:button>
</div>
