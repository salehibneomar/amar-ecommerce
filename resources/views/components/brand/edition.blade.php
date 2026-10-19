@if (appEdition() !== '')
    <x-ui.badge color="white" size="xs" {{ $attributes->class('tracking-wide uppercase') }}>{{ appEdition() }}</x-ui.badge>
@endif
