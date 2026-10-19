@props([
    'offset' => 400,
])

<button
    type="button"
    x-data="{ visible: false }"
    x-on:scroll.window.throttle.100ms="visible = window.scrollY > {{ (int) $offset }}"
    x-show="visible"
    x-transition.opacity
    x-cloak
    x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    aria-label="{{ __('Scroll to top') }}"
    {{ $attributes->class(['fixed end-6 bottom-6 z-10 flex size-11 items-center justify-center rounded-lg border border-brand-700/40 bg-brand-600/85 text-white backdrop-blur transition-colors hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-brand-900/60 dark:bg-brand-950/85 dark:hover:bg-brand-950']) }}
>
    <flux:icon.arrow-up variant="solid" class="size-5" />
</button>
