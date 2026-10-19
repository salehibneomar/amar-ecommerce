@props([
    'product',
    'meta' => null,
    'as' => 'a',
])

<x-frontend.cards.book-list-item
    {{ $attributes->merge($as === 'a' ? ['href' => route('shop.product', $product->slug), 'wire:navigate' => true] : []) }}
    :as="$as"
    :name="$product->name"
    :author="$product->author"
    :thumbnail-url="$product->cover_thumbnail_url"
    :meta="$meta ?? ($product->status->isInStock() ? ($product->is_free ? __('Free') : formatMoney($product->final_price)) : __('Out of Stock'))"
/>
