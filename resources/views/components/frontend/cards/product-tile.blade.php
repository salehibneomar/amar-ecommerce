@props([
    'product',
])

<x-frontend.cards.product-card
    {{ $attributes }}
    :product-id="$product->id"
    :href="route('shop.product', $product->slug)"
    :name="$product->name"
    :image-url="$product->image_url"
    :price="$product->final_price"
    :original-price="$product->has_discount ? $product->price : null"
    :discount="$product->has_discount ? $product->discount_percentage : null"
    :badge="$product->badge?->label()"
    :badge-color="$product->badge?->color() ?? 'amber'"
    :available="$product->status->isInStock()"
/>
