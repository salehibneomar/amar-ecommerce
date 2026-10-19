@props([
    'as' => 'div',
    'framed' => true,
    'interactive' => false,
])

<{{ $as }} {{ $attributes->class([
    'rounded-xl border border-line bg-surface shadow-card' => $framed,
    'group transition duration-200 hover:border-brand-300 dark:hover:border-brand-700' => $interactive,
]) }}>{{ $slot }}</{{ $as }}>
