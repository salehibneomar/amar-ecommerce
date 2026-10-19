@props([
    'model' => 'search',
    'placeholder' => __('Search...'),
    'label' => __('Search'),
])

<div class="w-full md:max-w-sm">
    <x-ui.search-input :model="$model" clearable {{ $attributes->merge(['placeholder' => $placeholder, 'aria-label' => $label]) }} />
</div>
