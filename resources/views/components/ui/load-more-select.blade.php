@props([
    'model',
    'options',
    'placeholder' => null,
    'hasMore' => false,
    'loadMore' => null,
    'loadMoreParams' => [],
    'live' => false,
])

<div @if ($loadMore) x-data="{ intercept(event) { if (event.target.value !== '__load_more__') return; event.stopPropagation(); event.target.value = $wire.get('{{ $model }}') ?? ''; if (event.type === 'change') $wire.call('{{ $loadMore }}', ...{{ Js::from($loadMoreParams) }}); } }" x-on:input.capture="intercept($event)" x-on:change.capture="intercept($event)" @endif>
    <flux:select {{ $attributes->merge([($live ? 'wire:model.live' : 'wire:model') => $model]) }}>
        @isset($placeholder)
            <flux:select.option value="">{{ $placeholder }}</flux:select.option>
        @endisset
        @foreach ($options as $value => $optionLabel)
            <flux:select.option wire:key="{{ $model }}-option-{{ $value }}" value="{{ $value }}">{{ $optionLabel }}</flux:select.option>
        @endforeach
        {{ $slot }}
        @if ($loadMore && $hasMore)
            <flux:select.option value="__load_more__">{{ __('Load more…') }}</flux:select.option>
        @endif
    </flux:select>
</div>
