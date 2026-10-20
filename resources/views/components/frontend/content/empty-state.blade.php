@props([
    'title',
    'icon' => null,
])

<div {{ $attributes->class('flex flex-col items-center gap-4 rounded-xl border border-dashed border-line px-6 py-16 text-center') }}>
    @if ($icon)
        <x-ui.icon-badge>
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </x-ui.icon-badge>
    @endif

    <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</p>

    {{ $slot }}
</div>
