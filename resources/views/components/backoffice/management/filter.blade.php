@props([
    'model',
    'label' => null,
    'options',
    'placeholder' => __('All'),
    'hasMore' => false,
    'loadMore' => null,
    'loadMoreParams' => [],
])

<x-ui.select class="md:w-auto md:min-w-36" :model="$model" :label="$label" :options="$options" :placeholder="$placeholder" :has-more="$hasMore" :load-more="$loadMore" :load-more-params="$loadMoreParams" live :aria-label="$label ?? $placeholder" />
