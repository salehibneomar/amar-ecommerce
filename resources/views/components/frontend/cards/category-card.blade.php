@props([
    'href',
    'name',
    'imageUrl' => null,
])

<x-frontend.cards.card as="a" :href="$href" wire:navigate interactive :padded="false" {{ $attributes->class('group flex flex-col gap-2 p-2') }}>
    <div class="relative aspect-square overflow-hidden rounded-lg bg-media">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $name }}" width="144" height="144" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105" />
        @else
            <div class="flex size-full items-center justify-center">
                <x-ui.icon-badge size="xl">
                    <span class="material-symbols-outlined text-3xl!">category</span>
                </x-ui.icon-badge>
            </div>
        @endif
    </div>
    <p class="line-clamp-2 px-1 pb-1 text-center text-xs font-semibold text-zinc-800 group-hover:text-brand-text sm:text-sm dark:text-zinc-100">{{ $name }}</p>
</x-frontend.cards.card>
