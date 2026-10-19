@props([
    'title',
    'description' => null,
])

<flux:card {{ $attributes->class(['flex min-w-0 flex-col gap-5']) }}>
    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
        <div class="min-w-0">
            <flux:heading size="lg">{{ $title }}</flux:heading>
            @if ($description)
                <flux:text>{{ $description }}</flux:text>
            @endif
        </div>

        {{ $actions ?? '' }}
    </div>

    {{ $slot }}
</flux:card>
