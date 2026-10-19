@props([
    'model' => 'perPage',
    'options' => [5, 10, 20, 50, 100],
    'default' => 10,
    'label' => __('Per page'),
    'placeholder' => __('Rows per page'),
])

<flux:field class="w-full md:w-32">
    <flux:label>{{ $label }}</flux:label>
    <flux:select wire:model.live="{{ $model }}" :aria-label="$label">
        <flux:select.option value="" disabled>{{ $placeholder }}</flux:select.option>
        @foreach ($options as $option)
            <flux:select.option value="{{ $option }}" :selected="$option === $default">
                {{ $option }}
            </flux:select.option>
        @endforeach
    </flux:select>
</flux:field>
