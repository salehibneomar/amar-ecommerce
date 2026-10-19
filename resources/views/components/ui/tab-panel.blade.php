@props([
    'name',
])

<div x-show="tab === @js($name)" x-cloak role="tabpanel" {{ $attributes }}>
    {{ $slot }}
</div>
