@props(['steps' => []])

<ol {{ $attributes->class(['flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2']) }}>
    @foreach ($steps as $step)
        <li @class(['flex items-center gap-2', 'sm:flex-1' => ! $loop->last])>
            <span @class([
                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                'bg-emerald-500 text-white' => $step['reached'] && ! $step['current'],
                'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-950' => $step['current'],
                'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' => ! $step['reached'],
            ])>
                @if ($step['reached'] && ! $step['current'])
                    <flux:icon.check variant="micro" />
                @else
                    {{ $loop->iteration }}
                @endif
            </span>
            <span @class([
                'text-sm sm:whitespace-nowrap',
                'font-medium text-zinc-900 dark:text-white' => $step['current'],
                'text-zinc-500 dark:text-zinc-400' => ! $step['current'],
            ])>{{ $step['label'] }}</span>
            @unless ($loop->last)
                <span @class([
                    'h-px flex-1 max-sm:hidden',
                    'bg-emerald-500' => $step['reached'] && ! $step['current'],
                    'bg-zinc-200 dark:bg-zinc-700' => ! $step['reached'] || $step['current'],
                ])></span>
            @endunless
        </li>
    @endforeach
</ol>
