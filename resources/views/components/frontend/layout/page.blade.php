@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class(['mx-auto flex max-w-main flex-col gap-6 px-4 pt-6 pb-10 sm:px-6']) }}>
    <div class="flex flex-col gap-3">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>

        {{ $notice ?? '' }}
    </div>

    {{ $slot }}
</div>
