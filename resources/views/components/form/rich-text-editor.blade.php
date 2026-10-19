@props([
    'label' => null,
    'placeholder' => null,
])

<flux:field>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    <div
        wire:ignore
        class="min-w-0"
        x-data="{ value: $wire.entangle(@js($attributes->wire('model')->value())) }"
        x-init="$watch('value', html => html !== $refs.editor.value && $refs.editor.editor.loadHTML(html ?? ''))"
        x-on:trix-initialize="$refs.editor.editor.loadHTML(value ?? '')"
        x-on:trix-change="value = $refs.editor.value"
    >
        <trix-editor x-ref="editor" placeholder="{{ $placeholder }}" class="rich-text"></trix-editor>
    </div>

    <flux:error :name="$attributes->wire('model')->value()" />
</flux:field>
