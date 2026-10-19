@props([
    'label',
    'field',
    'upload',
    'remove' => null,
    'selected' => null,
    'markedForRemoval' => false,
])

<flux:field wire:key="media-upload-{{ $upload }}" class="min-w-0 grid-cols-[minmax(0,1fr)] content-start">
    <flux:label>{{ $label }}</flux:label>

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
    >
        <input x-ref="input" type="file" wire:model="{{ $upload }}" accept="{{ $field['accept'] }}" class="sr-only" tabindex="-1" />

        @if ($markedForRemoval)
            <x-ui.banner tone="danger" size="none" class="flex h-16 items-center gap-3 rounded-lg border-dashed px-3">
                <x-ui.icon-badge tone="danger">
                    <flux:icon.trash variant="mini" />
                </x-ui.icon-badge>
                <p class="min-w-0 flex-1 text-sm text-red-700 dark:text-red-300">{{ __('Will be removed when you save.') }}</p>
                <x-ui.button type="button" size="sm" variant="ghost" wire:click="$set('{{ $remove }}', false)">
                    {{ __('Undo') }}
                </x-ui.button>
            </x-ui.banner>
        @elseif ($selected || $field['media'])
            <div
                class="flex h-16 items-center gap-3 rounded-lg border bg-surface px-3 transition"
                x-bind:class="dragging ? 'border-brand-500 ring-4 ring-brand-500/15' : 'border-line'"
            >
                @if ($field['image'] && $selected?->isPreviewable())
                    <img src="{{ $selected->temporaryUrl() }}" alt="" class="h-11 w-9 shrink-0 rounded-sm object-cover ring-1 ring-zinc-950/10" />
                @elseif ($field['image'] && ! $selected && $field['previewUrl'])
                    <img src="{{ $field['previewUrl'] }}" alt="" class="h-11 w-9 shrink-0 rounded-sm object-cover ring-1 ring-zinc-950/10" />
                @else
                    <div class="flex h-11 w-9 shrink-0 items-center justify-center rounded-sm bg-brand-50 text-brand-text dark:bg-brand-950">
                        <flux:icon :name="$field['image'] ? 'photo' : 'document-text'" />
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $selected ? $selected->getClientOriginalName() : $field['media']->file_name }}
                    </p>
                    <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="truncate">{{ $selected ? Number::fileSize($selected->getSize(), precision: 1) : $field['media']->human_readable_size }} · {{ $field['hint'] }}</span>
                        @if ($selected)
                            <x-ui.badge color="amber" inset="top bottom">{{ __('Not saved') }}</x-ui.badge>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center">
                    <x-ui.button type="button" size="sm" variant="ghost" square icon="arrow-path" x-on:click="$refs.input.click()" :aria-label="__('Replace')" :tooltip="__('Replace')" />
                    @if ($selected)
                        <x-ui.button type="button" size="sm" variant="ghost" square icon="x-mark" wire:click="$set('{{ $upload }}', null)" :aria-label="__('Discard')" :tooltip="__('Discard')" />
                    @elseif ($remove)
                        <x-ui.button type="button" size="sm" variant="ghost" color="danger" square icon="trash" wire:click="$set('{{ $remove }}', true)" :aria-label="__('Remove')" :tooltip="__('Remove')" />
                    @endif
                </div>
            </div>
        @else
            <button
                type="button"
                x-on:click="$refs.input.click()"
                class="group flex h-16 w-full items-center gap-3 rounded-lg border-2 border-dashed px-3 text-start transition focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500"
                x-bind:class="dragging
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/40'
                    : 'border-zinc-300 bg-zinc-50/60 hover:border-brand-400 hover:bg-brand-50/50 dark:border-zinc-700 dark:bg-zinc-900/40 dark:hover:bg-zinc-900'"
            >
                <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-surface text-zinc-500 ring-1 ring-zinc-950/5 transition group-hover:text-brand-text dark:text-zinc-300">
                    <flux:icon.arrow-up-tray variant="mini" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        <span class="font-semibold text-brand-text">{{ __('Click to upload') }}</span>
                        {{ __('or drag and drop') }}
                    </p>
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $field['hint'] }}</p>
                </div>
            </button>
        @endif

        <div x-show="uploading" style="display: none" class="mt-2 flex items-center gap-3">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full rounded-full bg-brand-600 transition-all duration-200" x-bind:style="`width: ${progress}%`"></div>
            </div>
            <span class="w-9 text-right text-xs tabular-nums text-zinc-500" x-text="`${progress}%`"></span>
        </div>
    </div>

    <flux:error name="{{ $upload }}" />
</flux:field>
