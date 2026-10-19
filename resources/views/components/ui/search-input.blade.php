@props([
    'model',
])

<div x-data="{ timer: null }">
    <flux:input {{ $attributes->merge([
        'wire:model' => $model,
        'x-on:input' => 'clearTimeout(timer); timer = setTimeout(() => $wire.$commit(), '.searchDebounceMs().')',
        'x-on:clear' => 'clearTimeout(timer); $wire.$commit()',
        'icon' => 'magnifying-glass',
        'autocomplete' => 'off',
    ]) }} />
</div>
