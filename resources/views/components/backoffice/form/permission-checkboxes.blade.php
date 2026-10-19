@props([
    'groups' => [],
    'locked' => [],
    'model' => 'permissions',
])

<flux:checkbox.group wire:model="{{ $model }}">
    <div class="grid gap-5 sm:grid-cols-2">
        @foreach ($groups as $group => $options)
            <div class="space-y-2">
                <flux:heading size="sm">{{ $group }}</flux:heading>
                @foreach ($options as $value => $label)
                    @if (in_array($value, $locked, true))
                        <flux:checkbox :value="$value" :label="$label" disabled />
                    @else
                        <flux:checkbox :value="$value" :label="$label" />
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</flux:checkbox.group>
<flux:error :name="$model" />
