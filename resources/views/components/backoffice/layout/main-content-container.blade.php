@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class(['flex flex-col gap-6']) }}>
    @if ($breadcrumbs)
        @push('back-url'){{ collect($breadcrumbs)->pluck('href')->filter()->last() }}@endpush
        <x-navigation.breadcrumb :items="$breadcrumbs" />
    @endif

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
            <flux:heading size="xl">{{ $title }}</flux:heading>
            @if ($subtitle || isset($badge))
                <div class="flex flex-wrap items-center justify-between gap-2">
                    @if ($subtitle)
                        <flux:text>{{ $subtitle }}</flux:text>
                    @endif
                    {{ $badge ?? '' }}
                </div>
            @endif
        </div>

        @isset($actions)
            <div>{{ $actions }}</div>
        @endisset
    </div>

    {{ $slot }}
</div>
