@props([
    'name',
    'author' => null,
    'thumbnailUrl' => null,
    'meta' => null,
    'note' => null,
    'as' => 'div',
    'locked' => false,
])

<{{ $as }} {{ $attributes->class(['group flex w-full min-w-0 items-center gap-3 rounded-xl shadow-card text-start border border-zinc-200/80 bg-white p-2.5 dark:border-zinc-800 dark:bg-zinc-950', 'cursor-pointer transition-colors hover:border-brand-300 dark:hover:border-brand-700' => $as !== 'div']) }}>
    <div class="flex h-12 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-brand-50 dark:bg-brand-950 text-brand-500">
        @if ($thumbnailUrl)
            <img src="{{ $thumbnailUrl }}" alt="{{ $name }}" class="h-full w-full object-cover" loading="lazy" />
        @else
            <span class="material-symbols-outlined text-xl opacity-80">auto_stories</span>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $name }}</p>
        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
            {{ $author }}@if ($author && $meta) · @endif{{ $meta }}
        </p>
        @if ($note)
            <p class="mt-0.5 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300">{{ $note }}</p>
        @endif
    </div>

    @if ($locked)
        <span class="material-symbols-outlined text-lg text-zinc-400">lock</span>
    @elseif ($as !== 'div')
        <span class="material-symbols-outlined text-xl text-brand-700 transition-transform group-hover:translate-x-0.5 dark:text-brand-400">chevron_right</span>
    @endif
</{{ $as }}>
