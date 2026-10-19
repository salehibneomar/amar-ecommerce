@props([
    'links' => [],
])

<div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @foreach ($links as $link)
        <a href="{{ $link['href'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-medium text-brand-800 transition-colors hover:bg-brand-100 dark:border-brand-800 dark:bg-brand-950 dark:text-brand-200 dark:hover:bg-brand-900">
            <span class="material-symbols-outlined text-base">{{ $link['icon'] }}</span>
            {{ $link['label'] }}
        </a>
    @endforeach
</div>
