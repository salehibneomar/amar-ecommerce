@props([
    'prompt',
    'href',
])

<div {{ $attributes->class(['space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400']) }}>
    <span>{{ $prompt }}</span>
    <flux:link :href="$href" wire:navigate>{{ $slot }}</flux:link>
</div>
