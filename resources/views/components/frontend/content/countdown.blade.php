@props([
    'to',
    'color' => 'red',
    'icon' => null,
    'live' => true,
    'separator' => ':',
])

@if ($to->isFuture())
    <span {{ $attributes->class([
        'inline-flex items-center gap-1 font-medium',
        'text-red-600 dark:text-red-400' => $color === 'red',
        'text-brand-text' => $color === 'brand',
        'text-zinc-500 dark:text-zinc-400' => $color === 'muted',
    ]) }}
        @if ($live)
            x-data="countdown({{ Js::from([
                'to' => $to->toIso8601String(),
                'now' => now()->toIso8601String(),
                'units' => ['d' => __('d'), 'h' => __('h'), 'm' => __('m'), 's' => __('s')],
                'separator' => $separator,
            ]) }})"
        @endif>
        <span class="inline-flex items-center gap-1" @if ($live) x-show="! done" @endif>
            @if ($icon)
                <span class="material-symbols-outlined text-[1.2em]!">{{ $icon }}</span>
            @endif
            {{ $slot->isEmpty() ? __('Ends in') : $slot }}
            <time datetime="{{ $to->toIso8601String() }}" class="tabular-nums" @if ($live) x-text="left" @endif>{{ formatTimeLeft($to, $separator) }}</time>
        </span>
        @if ($live && isset($expired))
            <span x-show="done" x-cloak>{{ $expired }}</span>
        @endif
    </span>
@else
    {{ $expired ?? '' }}
@endif
