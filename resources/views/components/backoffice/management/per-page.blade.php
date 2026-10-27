@props([
    'model' => 'perPage',
    'options' => [5, 10, 20, 50, 100],
    'label' => __('Per page'),
    'placeholder' => __('Rows per page'),
])

<x-ui.select class="md:w-32" :model="$model" :label="$label" :options="array_combine($options, $options)" :placeholder="$placeholder" required live :aria-label="$label" />
