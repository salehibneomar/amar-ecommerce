@props([
    'count' => 0,
    'tone' => 'danger',
])

@if ($count > 0)
    <x-ui.badge :color="$tone === 'accent' ? 'gold' : 'red'" :solid="$tone !== 'accent'" size="xs" circle {{ $attributes->class('absolute -end-1 -top-1') }}>{{ $count > 99 ? '99+' : $count }}</x-ui.badge>
@endif
