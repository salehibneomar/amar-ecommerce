@props([
    'modal' => false,
])

<div {{ $attributes->class(['flex flex-col-reverse gap-2 *:w-full sm:flex-row sm:items-center sm:*:w-auto', 'sm:justify-end' => $modal, 'sm:justify-start' => ! $modal]) }}>
    {{ $slot }}
</div>
