@props([
    'dismissible' => false,
])

<flux:modal :dismissible="$dismissible" {{ $attributes }}>{{ $slot }}</flux:modal>
