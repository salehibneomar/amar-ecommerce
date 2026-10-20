@props([
    'images' => [],
    'alt' => '',
    'name' => 'gallery-viewer',
    'icon' => 'image',
    'zoomable' => true,
    'firstLabel' => null,
    'thumbnailAction' => null,
    'empty' => null,
    'overlay' => null,
])

<div x-data="{ current: 0 }" {{ $attributes->class('space-y-3') }}>
    @if ($images || ! $empty)
        <div class="relative aspect-square overflow-hidden rounded-xl bg-media ring-2 ring-zinc-900/5">
            @forelse ($images as $index => $image)
                <img src="{{ $image['large'] }}" alt="{{ $alt }}" x-show="current === {{ $index }}" @if ($index > 0) style="display: none" loading="lazy" @endif class="size-full object-contain" />
            @empty
                <div class="flex size-full flex-col items-center justify-center gap-4 p-6 text-center text-zinc-700 dark:text-zinc-200">
                    <span class="material-symbols-outlined text-7xl! text-brand-500">{{ $icon }}</span>
                    <span class="text-lg font-semibold">{{ $alt }}</span>
                </div>
            @endforelse

            {{ $overlay }}

            @if ($images && $zoomable)
                <flux:modal.trigger :name="$name">
                    <x-ui.button size="sm" color="overlay" square icon="arrows-pointing-out" class="absolute end-3 top-3" :aria-label="__('View image')" :tooltip="__('View image')" />
                </flux:modal.trigger>
            @endif
        </div>
    @else
        {{ $empty }}
    @endif

    @if (count($images) > 1)
        <ul class="grid grid-cols-5 gap-2.5">
            @foreach ($images as $index => $image)
                <li class="relative">
                    <button type="button" x-on:click="current = {{ $index }}" aria-label="{{ __('Show image :number', ['number' => $index + 1]) }}"
                        class="relative block w-full overflow-hidden rounded-lg border bg-media outline-offset-2 transition focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                        x-bind:class="current === {{ $index }} ? 'border-brand-500 outline-2 outline-brand-500' : 'border-line hover:border-brand-400 dark:hover:border-brand-600'">
                        <img src="{{ $image['thumbnail'] }}" alt="" loading="lazy" class="aspect-square w-full object-cover" />
                        @if ($firstLabel && $index === 0)
                            <span class="absolute start-1.5 top-1.5">
                                <x-ui.badge color="white" size="xs" overlay>{{ $firstLabel }}</x-ui.badge>
                            </span>
                        @endif
                    </button>
                    @if ($thumbnailAction && $index > 0)
                        <span class="absolute end-1.5 top-1.5">
                            <x-ui.button size="xs" color="overlay" square icon="star"
                                x-on:click="$wire.call('{{ $thumbnailAction }}', {{ $image['id'] }}).then(() => current = 0)"
                                wire:loading.attr="disabled" wire:target="{{ $thumbnailAction }}"
                                :aria-label="__('Set as Thumbnail')" :tooltip="__('Set as Thumbnail')" />
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($images && $zoomable)
        <x-ui.modal :name="$name" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-4xl">
            <div class="space-y-4">
                <flux:heading size="lg">{{ $alt }}</flux:heading>
                @foreach ($images as $index => $image)
                    <img src="{{ $image['large'] }}" alt="{{ $alt }}" x-show="current === {{ $index }}" @if ($index > 0) style="display: none" @endif loading="lazy" class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain" />
                @endforeach
            </div>
        </x-ui.modal>
    @endif
</div>
