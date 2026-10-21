@props([
    'to',
    'color' => 'red',
    'icon' => null,
    'live' => true,
    'separator' => ':',
    'variant' => 'inline',
])

@if ($to->isFuture())
    <span {{ $attributes->class([
        'inline-flex items-center gap-1 font-medium' => $variant === 'inline',
        'inline-flex flex-col items-center gap-2' => $variant === 'boxes',
        'text-red-600 dark:text-red-400' => $variant === 'inline' && $color === 'red',
        'text-brand-text' => $variant === 'inline' && $color === 'brand',
        'text-zinc-500 dark:text-zinc-400' => $variant === 'inline' && $color === 'muted',
    ]) }}
        @if ($live)
            x-data="countdown({{ Js::from([
                'to' => $to->toIso8601String(),
                'now' => now()->toIso8601String(),
                'units' => ['d' => __('d'), 'h' => __('h'), 'm' => __('m'), 's' => __('s')],
                'separator' => $separator,
            ]) }})"
        @endif>
        @if ($variant === 'boxes')
            <span class="flex flex-wrap items-center gap-2" @if ($live) x-show="! done" @endif>
                <span class="inline-flex items-center gap-1 text-xs font-semibold tracking-wider text-zinc-600 uppercase dark:text-zinc-300">
                    @if ($icon)
                        <span class="material-symbols-outlined text-[1.2em]!">{{ $icon }}</span>
                    @endif
                    {{ $slot->isEmpty() ? __('Ends in') : $slot }}
                </span>
                <time datetime="{{ $to->toIso8601String() }}" class="flex gap-1.5">
                    @foreach (timeLeftParts($to) as $unit => $count)
                        @if ($unit !== 'd' || $count > 0)
                            <span class="flex min-w-11 flex-col items-center gap-0.5 rounded-lg border border-line bg-surface px-1.5 py-1.5 shadow-card" @if ($live && $unit === 'd') x-show="parts.d !== '00'" @endif>
                                <span @class([
                                    'text-lg leading-none font-bold tabular-nums',
                                    'text-red-600 dark:text-red-400' => $color === 'red',
                                    'text-brand-text' => $color === 'brand',
                                    'text-zinc-700 dark:text-zinc-200' => $color === 'muted',
                                ]) @if ($live) x-text="parts.{{ $unit }}" @endif>{{ sprintf('%02d', $count) }}</span>
                                <span class="text-[10px] font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ ['d' => __('Day'), 'h' => __('Hrs'), 'm' => __('Min'), 's' => __('Sec')][$unit] }}</span>
                            </span>
                        @endif
                    @endforeach
                </time>
            </span>
        @else
            <span class="inline-flex items-center gap-1" @if ($live) x-show="! done" @endif>
                @if ($icon)
                    <span class="material-symbols-outlined text-[1.2em]!">{{ $icon }}</span>
                @endif
                {{ $slot->isEmpty() ? __('Ends in') : $slot }}
                <time datetime="{{ $to->toIso8601String() }}" class="tabular-nums" @if ($live) x-text="left" @endif>{{ formatTimeLeft($to, $separator) }}</time>
            </span>
        @endif
        @if ($live && isset($expired))
            <span x-show="done" x-cloak>{{ $expired }}</span>
        @endif
    </span>
@else
    {{ $expired ?? '' }}
@endif
