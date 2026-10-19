@props(['href'])

@if (filled($href))
    <x-ui.button variant="subtle" square icon="arrow-left" :href="$href" wire:navigate data-test="back-button" :aria-label="__('Go back')" {{ $attributes->class('cursor-pointer') }} />
@endif
