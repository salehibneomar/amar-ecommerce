@props([
    'title' => null,
    'description' => null,
    'framed' => true,
    'padded' => true,
    'interactive' => false,
    'as' => 'div',
])

<x-ui.card :as="$as" :framed="$framed" :interactive="$interactive" {{ $attributes->class(['space-y-6 p-6' => $padded, 'hover:shadow-md' => $interactive]) }}>
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
</x-ui.card>
