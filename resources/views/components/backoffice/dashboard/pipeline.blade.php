@props([
    'total',
    'label',
    'stages' => [],
])

<div {{ $attributes->class(['space-y-4']) }}>
    <div class="flex items-baseline gap-2">
        <span class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $total }}</span>
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
    </div>

    <div class="flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
        @foreach ($stages as $stage)
            <div class="{{ $stage['color'] }} h-full" style="width: {{ $stage['percent'] }}%"></div>
        @endforeach
    </div>

    <ul class="space-y-2.5">
        @foreach ($stages as $stage)
            <li class="flex items-center gap-2.5 text-sm">
                <span class="{{ $stage['color'] }} size-2.5 shrink-0 rounded-full"></span>
                <span class="min-w-0 flex-1 truncate text-zinc-600 dark:text-zinc-300">{{ $stage['label'] }}</span>
                <span class="font-medium text-zinc-900 dark:text-white">{{ $stage['value'] }}</span>
            </li>
        @endforeach
    </ul>
</div>
