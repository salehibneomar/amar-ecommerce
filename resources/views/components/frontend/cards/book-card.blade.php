@props([
    'name',
    'author' => null,
    'coverUrl' => null,
    'badge' => null,
    'badgeColor' => 'amber',
    'as' => 'div',
    'actionLabel' => null,
    'locked' => false,
])

<{{ $as }} {{ $attributes->class(['group flex w-full gap-4 overflow-hidden rounded-2xl shadow-card border border-zinc-200/80 bg-white p-3 text-start dark:border-zinc-800 dark:bg-zinc-950', 'cursor-pointer hover:border-brand-300 dark:hover:border-brand-700' => $as !== 'div']) }}>
    <div class="relative flex aspect-[2/3] w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-50 dark:bg-brand-950 text-brand-500">
        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $name }}" class="h-full w-full object-cover" loading="lazy" />
        @else
            <span class="material-symbols-outlined text-3xl opacity-80">auto_stories</span>
        @endif
        @if ($locked)
            <span class="absolute end-1.5 top-1.5 flex size-6 items-center justify-center rounded-full bg-black/40 backdrop-blur">
                <span class="material-symbols-outlined text-sm">lock</span>
            </span>
        @endif
    </div>
    <div class="flex min-w-0 flex-1 flex-col py-1">
        @if ($badge)
            <x-ui.badge :color="$badgeColor" class="mb-2 self-start">{{ $badge }}</x-ui.badge>
        @endif
        <h3 class="line-clamp-2 font-medium text-zinc-900 dark:text-white">{{ $name }}</h3>
        @if ($author)
            <p class="mt-1 truncate text-xs text-zinc-500">{{ $author }}</p>
        @endif
        @if ($actionLabel)
            <p class="mt-auto flex items-center gap-1 pt-3 text-sm font-medium text-brand-700 dark:text-brand-400">{{ $actionLabel }}<span class="material-symbols-outlined text-base transition-transform group-hover:translate-x-0.5">arrow_forward</span></p>
        @endif
    </div>
</{{ $as }}>
