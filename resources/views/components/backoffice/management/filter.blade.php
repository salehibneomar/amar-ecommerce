@props([
    'model',
    'label' => null,
    'options',
    'placeholder' => __('All'),
    'hasMore' => false,
    'loadMore' => null,
    'loadMoreParams' => [],
])

<flux:field class="w-full md:w-auto md:min-w-36">
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif
    <x-ui.load-more-select :model="$model" :options="$options" :placeholder="$placeholder" :has-more="$hasMore" :load-more="$loadMore" :load-more-params="$loadMoreParams" live :aria-label="$label ?? $placeholder" />
</flux:field>
