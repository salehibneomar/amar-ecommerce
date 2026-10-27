@props([
    'model',
    'options',
    'label' => null,
    'placeholder' => null,
    'live' => false,
    'required' => false,
    'disabled' => false,
    'hasMore' => false,
    'loadMore' => null,
    'loadMoreParams' => [],
])

<div
    {{ $attributes->only('class')->class('relative w-full') }}
    x-data="select({{ Js::from(['model' => $model, 'live' => (bool) $live, 'placeholder' => $placeholder ?? '', 'loadMore' => $loadMore, 'loadMoreParams' => $loadMoreParams]) }})"
    x-on:click.outside="open = false"
    x-on:keydown.escape.prevent.stop="close()"
    x-on:keydown.down.prevent="open ? $focus.within($refs.panel).wrap().next() : open = true"
    x-on:keydown.up.prevent="open && $focus.within($refs.panel).wrap().previous()"
>
    <flux:field>
        @if ($label)
            <flux:label>{{ $label }}</flux:label>
        @endif

        <button
            type="button"
            x-ref="trigger"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
            aria-haspopup="listbox"
            {{ $attributes->except('class') }}
            @disabled($disabled)
            @if ($required) aria-required="true" @endif
            @error($model) aria-invalid="true" @enderror
            @class([
                'flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white ps-3 pe-2 text-start text-base text-zinc-700 shadow-xs sm:text-sm',
                'disabled:text-zinc-500 disabled:shadow-none dark:bg-white/10 dark:text-zinc-300 dark:disabled:bg-white/[7%] dark:disabled:text-zinc-400',
                'border-red-500' => $errors->has($model),
                'border-zinc-200 border-b-zinc-300/80 dark:border-white/10' => ! $errors->has($model),
            ])
        >
            <span class="truncate" x-bind:class="{ 'text-zinc-400': String(value ?? '') === '' }" x-text="text">{{ $placeholder }}</span>
            <flux:icon.chevron-up-down class="size-4 shrink-0 text-zinc-400" />
        </button>

        @if ($label)
            <flux:error :name="$model" />
        @endif
    </flux:field>

    <div
        x-ref="panel"
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute inset-x-0 z-5 mt-1 overflow-hidden rounded-xl border border-line bg-surface shadow-xs"
    >
        <div role="listbox" class="max-h-64 overflow-y-auto p-1">
            @if ($placeholder !== null && ! $required)
                <button type="button" role="option" x-bind:aria-selected="is('')" x-on:click="pick('')" data-select-option="" data-select-label="{{ $placeholder }}"
                    class="w-full rounded-md px-2 py-1.5 text-start text-sm text-zinc-500 hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-hidden dark:text-zinc-300 dark:hover:bg-white/10 dark:focus:bg-white/10">
                    {{ $placeholder }}
                </button>
            @endif

            @foreach ($options as $value => $option)
                <button
                    wire:key="{{ $model }}-option-{{ $value }}"
                    type="button"
                    role="option"
                    x-bind:aria-selected="is({{ Js::from((string) $value) }})"
                    x-bind:class="{ 'font-medium': is({{ Js::from((string) $value) }}) }"
                    x-on:click="pick({{ Js::from((string) $value) }})"
                    @disabled(is_array($option) && $option['disabled'])
                    data-select-option="{{ $value }}"
                    data-select-label="{{ is_array($option) ? $option['label'] : $option }}"
                    class="flex w-full items-center justify-between gap-2 rounded-md px-2 py-1.5 text-start text-sm text-zinc-800 hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-hidden disabled:pointer-events-none disabled:opacity-50 dark:text-white dark:hover:bg-white/10 dark:focus:bg-white/10"
                >
                    <span class="truncate">{{ is_array($option) ? $option['label'] : $option }}</span>
                    <flux:icon.check class="size-4 shrink-0 text-brand-text" x-show="is({{ Js::from((string) $value) }})" />
                </button>
            @endforeach
        </div>

        @if ($loadMore && $hasMore)
            <div class="border-t border-line p-1">
                <button type="button" x-on:click="loadMoreOptions()" x-bind:disabled="loading"
                    class="flex w-full items-center justify-center gap-2 rounded-md px-2 py-1.5 text-xs text-zinc-800 hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-hidden disabled:opacity-60 dark:text-white dark:hover:bg-white/10 dark:focus:bg-white/10">
                    <flux:icon.loading class="size-4" x-show="loading" x-cloak />
                    {{ __('Load More') }}
                </button>
            </div>
        @endif
    </div>
</div>
