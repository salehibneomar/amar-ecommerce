@props([
    'links' => [],
])

<div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @foreach ($links as $link)
        <x-ui.button size="sm" :href="$link['href']" target="_blank" rel="noopener">
            <span class="material-symbols-outlined text-base">{{ $link['icon'] }}</span>
            {{ $link['label'] }}
        </x-ui.button>
    @endforeach
</div>
