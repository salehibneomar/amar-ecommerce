@props([
    'model',
    'levels',
    'labels' => [__('Category'), __('Sub-category'), __('Sub-sub-category')],
    'placeholder' => null,
    'subPlaceholder' => __('None'),
    'filter' => false,
    'required' => false,
])

@foreach ($levels as $level => $batch)
    @continue($filter && $batch['disabled'])
    <div wire:key="{{ $model }}-level-{{ $level }}">
        @if ($filter)
            <x-backoffice.management.filter model="{{ $model }}.{{ $level }}" :label="$labels[$level] ?? null" :options="$batch['options']" :has-more="$batch['has_more']" load-more="loadMoreCategoryLevel" :load-more-params="[$model, $level]" />
        @else
            <x-ui.select model="{{ $model }}.{{ $level }}" :label="$labels[$level] ?? null" :options="$batch['options']" :placeholder="$level === 0 ? $placeholder : $subPlaceholder" :has-more="$batch['has_more']" load-more="loadMoreCategoryLevel" :load-more-params="[$model, $level]" :required="$required && $level === 0" :disabled="$batch['disabled']" live />
        @endif
    </div>
@endforeach
