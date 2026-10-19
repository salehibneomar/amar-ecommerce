@props([
    'title',
    'items' => [],
    'size' => '2xl',
    'wide' => [__('Description')],
])

<x-backoffice.management.form-modal :title="$title" :size="$size" :show-default-footer="false" {{ $attributes }}>
    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
        @foreach ($items as $label => $value)
            <x-backoffice.management.detail-item :label="$label" @class(['col-span-full' => in_array($label, $wide, true)])>
                @if (is_bool($value))
                    {{ $value ? __('Yes') : __('No') }}
                @else
                    {{ filled($value) ? $value : '-' }}
                @endif
            </x-backoffice.management.detail-item>
        @endforeach
    </dl>
</x-backoffice.management.form-modal>
