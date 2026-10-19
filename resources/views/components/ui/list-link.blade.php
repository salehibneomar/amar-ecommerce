@props([
    'href',
    'icon',
    'title',
    'description' => null,
])

<x-ui.list-item :href="$href" {{ $attributes }}>
    <x-slot:leading>
        <flux:icon :icon="$icon" variant="outline" class="size-5 shrink-0 text-zinc-600 transition group-hover:text-brand-text dark:text-zinc-300" />
    </x-slot:leading>
    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $title }}</p>
    @if ($description)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif
</x-ui.list-item>
