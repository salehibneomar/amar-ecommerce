@props([
    'label',
    'gallery',
    'upload',
])

<flux:field wire:key="media-gallery-{{ $upload }}" class="min-w-0 grid-cols-[minmax(0,1fr)] content-start">
    <div class="flex items-center justify-between gap-3">
        <flux:label>{{ $label }}</flux:label>
        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __(':count of :max', ['count' => $gallery['count'], 'max' => $gallery['max']]) }}</span>
    </div>

    <div
        x-data="{ dragging: false, uploading: false, progress: 0 }"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="
            dragging = false;
            if ($event.dataTransfer.files.length) {
                $refs.input.files = $event.dataTransfer.files;
                $refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        "
        x-on:livewire-upload-start="uploading = true; progress = 0"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:livewire-upload-finish="uploading = false"
        x-on:livewire-upload-error="uploading = false"
        class="space-y-3"
    >
        <input x-ref="input" type="file" multiple wire:model="{{ $upload }}" accept="{{ $gallery['accept'] }}" class="sr-only" tabindex="-1" />

        <button
            type="button"
            x-on:click="$refs.input.click()"
            class="group flex h-16 w-full items-center gap-3 rounded-lg border-2 border-dashed px-3 text-start transition focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
            x-bind:class="dragging
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/40'
                : 'border-zinc-300 bg-zinc-50/60 hover:border-brand-400 hover:bg-brand-50/50 dark:border-zinc-700 dark:bg-zinc-900/40 dark:hover:bg-zinc-900'"
        >
            <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-surface text-zinc-500 ring-1 ring-zinc-950/5 transition group-hover:text-brand-text dark:text-zinc-300">
                <flux:icon.arrow-up-tray class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    <span class="font-semibold text-brand-text">{{ __('Click to upload') }}</span>
                    {{ __('or drag and drop') }}
                </p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ __(':hint · up to :max images, the first one is the thumbnail', ['hint' => $gallery['hint'], 'max' => $gallery['max']]) }}</p>
            </div>
        </button>

        @if ($gallery['items'])
            <ul class="divide-y divide-line overflow-hidden rounded-lg border border-line">
                @foreach ($gallery['items'] as $item)
                    <li wire:key="gallery-item-{{ $item['key'] }}">
                        <x-ui.list-item class="px-3 py-2.5">
                            <x-slot:leading>
                                @if ($item['url'])
                                    <img src="{{ $item['url'] }}" alt="{{ $item['name'] }}" @class([
                                        'size-12 shrink-0 rounded-md object-cover ring-1 ring-zinc-950/10',
                                        'opacity-40 grayscale' => $item['is_removed'],
                                    ]) />
                                @else
                                    <x-ui.icon-badge size="lg">
                                        <flux:icon.photo />
                                    </x-ui.icon-badge>
                                @endif
                            </x-slot:leading>
                            <p @class(['truncate text-sm font-medium text-zinc-800 dark:text-zinc-100', 'line-through' => $item['is_removed']])>{{ $item['name'] }}</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                @if ($item['is_new'])
                                    <x-ui.badge color="amber" size="xs">{{ __('Not saved') }}</x-ui.badge>
                                @endif
                                @if ($item['is_thumbnail'])
                                    <x-ui.badge color="brand" size="xs">{{ __('Thumbnail') }}</x-ui.badge>
                                @endif
                                @if ($item['is_removed'])
                                    <x-ui.badge color="red" size="xs">{{ __('Removed') }}</x-ui.badge>
                                @endif
                            </div>
                            <x-slot:trailing>
                                <x-backoffice.management.actions>
                                    <x-backoffice.management.action icon="chevron-up" :label="__('Move Up')" wire:click="moveImage('{{ $item['key'] }}', -1)" :disabled="$loop->first" />
                                    <x-backoffice.management.action icon="chevron-down" :label="__('Move Down')" wire:click="moveImage('{{ $item['key'] }}', 1)" :disabled="$loop->last" />
                                    @unless ($item['is_thumbnail'] || $item['is_removed'])
                                        <x-backoffice.management.action icon="star" :label="__('Set as Thumbnail')" wire:click="chooseThumbnail('{{ $item['key'] }}')" />
                                    @endunless
                                    @if ($item['is_new'])
                                        <x-backoffice.management.action icon="x-mark" :label="__('Discard')" variant="danger" wire:click="discardImageUpload({{ $item['upload_index'] }})" />
                                    @else
                                        <x-backoffice.management.action :icon="$item['is_removed'] ? 'arrow-uturn-left' : 'trash'" :label="$item['is_removed'] ? __('Undo') : __('Remove')" :variant="$item['is_removed'] ? 'filled' : 'danger'" wire:click="toggleRemoveImage({{ $item['id'] }})" />
                                    @endif
                                </x-backoffice.management.actions>
                            </x-slot:trailing>
                        </x-ui.list-item>
                    </li>
                @endforeach
            </ul>
        @endif

        <div x-show="uploading" style="display: none" class="flex items-center gap-3">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-brand-600 transition-all duration-200" x-bind:style="`width: ${progress}%`"></div>
            </div>
            <span class="w-9 text-right text-xs tabular-nums text-zinc-500" x-text="`${progress}%`"></span>
        </div>
    </div>

    <flux:error name="{{ $upload }}" />
    <flux:error name="{{ $upload }}.*" />
</flux:field>
