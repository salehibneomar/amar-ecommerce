@props([
    'value',
    'label' => null,
    'description' => null,
])

<flux:field {{ $attributes->class('min-w-0') }}>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <div class="flex min-w-0 items-center gap-1">
        <span class="min-w-0 truncate text-sm text-zinc-800 dark:text-zinc-100">{{ $value }}</span>

        <flux:tooltip :content="__('Copy')">
            <x-ui.button
                type="button"
                variant="ghost"
                size="xs"
                square
                icon="square-2-stack"
                :aria-label="__('Copy')"
                x-data
                x-on:click="navigator.clipboard.writeText({{ Js::from($value) }}).then(() => $dispatch('toast-show', { slots: { text: {{ Js::from(__('Copied.')) }} }, dataset: { variant: 'success' } }))"
            />
        </flux:tooltip>
    </div>
</flux:field>
