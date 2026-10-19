@props([
    'label',
    'field',
    'actionLabel' => null,
])

<div class="min-w-0 space-y-2">
    <flux:label>{{ $label }}</flux:label>

    @if ($field['media'])
        <button type="button" {{ $attributes->class('group relative flex h-64 w-full items-center justify-center overflow-hidden rounded-xl border border-line bg-subtle transition hover:border-brand-400 hover:shadow-sm focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-500') }}>
            @if ($field['image'] && $field['previewUrl'])
                <img src="{{ $field['previewUrl'] }}" alt="{{ $label }}" class="h-full w-auto object-contain py-3" />
            @else
                <div class="flex flex-col items-center gap-3 px-4 text-center">
                    <x-ui.icon-badge size="2xl">
                        <flux:icon :name="$field['image'] ? 'photo' : 'document-text'" class="size-8" />
                    </x-ui.icon-badge>
                    <div class="min-w-0 max-w-full">
                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $field['media']->file_name }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $field['media']->human_readable_size }}</p>
                    </div>
                </div>
            @endif

            <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1.5 bg-zinc-950/60 py-2 text-xs font-medium text-white opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
                <flux:icon.arrows-pointing-out variant="micro" />
                {{ $actionLabel ?? __('Open') }}
            </span>
        </button>
    @else
        <div class="flex h-64 flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line text-zinc-400">
            <flux:icon :name="$field['image'] ? 'photo' : 'document-text'" class="size-8" />
            <p class="text-sm">{{ __('Not uploaded') }}</p>
        </div>
    @endif
</div>
