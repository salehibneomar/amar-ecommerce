@props([
    'title' => null,
    'description' => null,
    'muted' => false,
    'width' => 'max-w-main',
])

<section {{ $attributes->class(['scroll-mt-20', 'bg-subtle' => $muted]) }}>
    <div @class(['mx-auto px-4 py-20 sm:px-6', $width])>
        @if ($title)
            <div @class([
                'flex flex-wrap items-end justify-between gap-4' => isset($actions),
                'mx-auto max-w-2xl text-center' => ! isset($actions),
            ])>
                <div>
                    <h2 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $title }}</h2>
                    @if ($description)
                        <p class="mt-3 text-zinc-600 dark:text-zinc-300">{{ $description }}</p>
                    @endif
                </div>

                {{ $actions ?? '' }}
            </div>
        @endif

        <div @class(['mt-12' => $title])>
            {{ $slot }}
        </div>
    </div>
</section>
