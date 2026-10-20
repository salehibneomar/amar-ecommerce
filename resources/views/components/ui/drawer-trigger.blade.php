@props([
    'name',
])

<div
    x-data="{ expanded: false }"
    x-effect="$el.firstElementChild?.setAttribute('aria-expanded', expanded)"
    x-on:drawer-state.window="$event.detail.name === @js($name) && (expanded = $event.detail.open)"
    x-on:click="$dispatch('drawer-open', { name: @js($name) })"
    {{ $attributes->class(['contents']) }}
>{{ $slot }}</div>
