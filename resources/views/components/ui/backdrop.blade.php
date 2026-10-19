@props([
    'show',
])

<div
    x-bind:data-open="{{ $show }}"
    x-on:click="{{ $show }} = false"
    {{ $attributes->class(['pointer-events-none fixed inset-0 z-50 bg-zinc-950/50 opacity-0 backdrop-blur-sm transition-opacity duration-200 data-open:pointer-events-auto data-open:opacity-100']) }}
></div>
