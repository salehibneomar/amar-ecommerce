@props([
    'label',
])

<div {{ $attributes }}>
    <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
    <dd class="mt-1 text-sm text-zinc-800 dark:text-zinc-200">{{ $slot }}</dd>
</div>
