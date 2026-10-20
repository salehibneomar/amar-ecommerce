@props([
    'name',
    'label',
    'side' => 'end',
    'static' => false,
    'mobile' => false,
])

<div
    x-data="{ open: false }"
    x-init="$watch('open', (value) => $dispatch('drawer-state', { name: @js($name), open: value }))"
    x-on:drawer-open.window="open = $event.detail.name === @js($name)"
    x-on:drawer-close.window="open = false"
    x-on:keydown.escape.window="open = false"
    @if ($static || $mobile) x-on:resize.window="window.matchMedia('(min-width: 64rem)').matches && (open = false)" @endif
    class="contents"
>
    @unless ($static)
        <template x-teleport="body">
    @endunless
    <div @class(['lg:hidden' => $mobile, 'max-lg:contents' => $static])>
        <x-ui.backdrop show="open" @class(['lg:hidden' => $static]) />

        <aside
            x-bind:data-open="open"
            x-trap.inert.noscroll="open"
            x-bind:role="open && 'dialog'"
            x-bind:aria-modal="open"
            aria-label="{{ $label }}"
            {{ $attributes->class([
                'invisible fixed inset-y-0 z-50 flex w-full max-w-md flex-col glass-solid transition-[translate,visibility] duration-200 ease-out data-open:visible data-open:translate-x-0',
                'end-0 translate-x-full rtl:-translate-x-full' => $side === 'end',
                'start-0 -translate-x-full rtl:translate-x-full' => $side === 'start',
                'lg:visible lg:sticky lg:top-24 lg:bottom-auto lg:z-auto lg:w-auto lg:max-w-none lg:translate-none lg:bg-transparent lg:shadow-none lg:backdrop-filter-none lg:transition-none dark:lg:bg-transparent' => $static,
            ]) }}
        >
            <div @class(['flex h-16 shrink-0 items-center justify-between gap-2 border-b border-glass-line px-4', 'lg:hidden' => $static])>
                {{ $header }}
            </div>

            <div @class(['flex-1 overflow-y-auto p-4', 'lg:overflow-visible lg:p-0' => $static])>
                {{ $slot }}
            </div>

            @isset($footer)
                <div @class(['shrink-0 border-t border-glass-line p-4', 'lg:hidden' => $static])>
                    {{ $footer }}
                </div>
            @endisset
        </aside>
    </div>
    @unless ($static)
        </template>
    @endunless
</div>
