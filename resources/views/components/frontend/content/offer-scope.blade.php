@props([
    'offer',
    'withPercentage' => false,
])

<span {{ $attributes }}>
    @if ($withPercentage)
        {{ $offer['categories'] === [] ? __('extra :percent% off on your total purchase', ['percent' => $offer['percentage']]) : __('extra :percent% off on your total purchase of :categories products', ['percent' => $offer['percentage'], 'categories' => implode(', ', $offer['categories'])]) }}
    @else
        {{ $offer['categories'] === [] ? __('On your total purchase') : __('On your total purchase of :categories products', ['categories' => implode(', ', $offer['categories'])]) }}
    @endif
</span>
