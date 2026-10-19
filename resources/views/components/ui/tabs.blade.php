@props([
    'tabs' => [],
    'active' => null,
])

<div x-data="{ tab: @js($active ?? array_key_first($tabs)) }" {{ $attributes->class(['space-y-5']) }}>
    <div class="flex gap-1 border-b border-line" role="tablist">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                role="tab"
                x-on:click="tab = @js($key)"
                x-bind:aria-selected="tab === @js($key)"
                x-bind:class="tab === @js($key) ? 'border-brand-600 text-zinc-900 dark:border-brand-400 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white'"
                class="-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors"
            >{{ $label }}</button>
        @endforeach
    </div>

    {{ $slot }}
</div>
