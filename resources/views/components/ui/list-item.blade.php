@props([
    'href' => null,
    'chevron' => true,
])

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class(['group flex items-center gap-3 px-4 py-3.5', 'transition hover:bg-row-hover' => $href]) }}>
    {{ $leading ?? '' }}
    <div class="min-w-0 flex-1">
        {{ $slot }}
    </div>
    {{ $trailing ?? '' }}
    @if ($href && $chevron)
        <flux:icon.chevron-right class="size-5 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-brand-text" />
    @endif
</{{ $href ? 'a' : 'div' }}>
