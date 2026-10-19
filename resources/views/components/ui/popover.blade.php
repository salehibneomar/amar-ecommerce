@props([
    'label',
])

<div
    x-data="{
        open: false,
        top: 0,
        place() {
            this.top = window.matchMedia('(min-width: 40rem)').matches
                ? this.$refs.trigger.getBoundingClientRect().bottom + 5
                : (this.$refs.trigger.closest('[data-popover-anchor]') ?? this.$refs.trigger).getBoundingClientRect().bottom
        },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window="open && place()"
    class="flex"
>
    <span x-ref="trigger" class="inline-flex" x-on:click="place(); open = ! open">
        {{ $trigger }}
    </span>

    <template x-teleport="body">
        <div
            x-bind:data-open="open"
            x-bind:style="{ top: top + 'px' }"
            x-trap="open"
            x-on:click="$event.target.closest('a') && (open = false)"
            x-on:click.outside="open && ! $refs.trigger.contains($event.target) && (open = false)"
            role="dialog"
            aria-label="{{ $label }}"
            {{ $attributes->class(['invisible fixed inset-x-0 z-50 mx-auto -translate-y-2 border-y border-line bg-surface opacity-0 shadow-md transition-[opacity,translate,visibility] duration-200 data-open:visible data-open:translate-y-0 data-open:opacity-100 sm:inset-x-4 sm:max-w-xl sm:rounded-lg sm:border-x']) }}
        >
            {{ $slot }}
        </div>
    </template>
</div>
