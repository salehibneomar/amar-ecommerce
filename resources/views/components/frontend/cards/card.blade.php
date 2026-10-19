@props([
    'title' => null,
    'description' => null,
    'framed' => true,
    'padded' => true,
])

<div {{ $attributes->class(['rounded-2xl shadow-card border border-line bg-surface' => $framed, 'space-y-6 p-6' => $padded]) }}>
    @if ($title)
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif

    {{ $slot }}
</div>
