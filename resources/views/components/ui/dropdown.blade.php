@props([
    'position' => 'bottom',
    'align' => 'end',
    'gap' => 5,
    'menuClass' => null,
    'lockScroll' => false,
])

<div
    class="contents"
    x-data="dropdown({ position: @js($position), align: @js($align), gap: @js($gap) })"
    x-on:beforetoggle.capture="$event.newState === 'open' && place($event.target)"
    x-on:resize.window="replace()"
>
    <flux:dropdown :position="$position" :align="$align" :gap="$gap" {{ $attributes }}>
        {{ $trigger }}

        <flux:menu @class([$menuClass, 'dropdown-lock-scroll' => $lockScroll])>
            {{ $slot }}
        </flux:menu>
    </flux:dropdown>
</div>
