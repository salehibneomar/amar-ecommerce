@props([
    'notification',
])

<div {{ $attributes->class('grid w-full min-w-0 gap-0.5 whitespace-normal text-start') }}>
    <span class="flex items-start gap-2">
        @unless ($notification['status']->isRead())
            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500" aria-label="{{ $notification['status']->label() }}"></span>
        @endunless
        <span @class(['text-sm text-zinc-900 dark:text-white', 'font-semibold' => ! $notification['status']->isRead()])>{{ $notification['message'] }}</span>
    </span>
    <span class="text-xs text-zinc-500 dark:text-zinc-400" title="{{ $notification['date'] }}">{{ $notification['label'] }} · {{ $notification['time'] }}</span>
</div>
