@props([
    'product',
    'owned' => false,
])

<x-frontend.cards.product-card
    {{ $attributes }}
    :product-id="$product->id"
    :href="route('shop.product', $product->slug)"
    :name="$product->name"
    :author="$product->author"
    :cover-url="$product->cover_preview_url"
    :price="$product->final_price"
    :original-price="$product->has_discount ? $product->price : null"
    :discount="$product->has_discount ? $product->discount_percentage : null"
    :free="$product->is_free"
    :owned="$owned"
    :readable="$product->is_digital"
    :badge="$product->badge?->label()"
    :badge-color="$product->badge?->color() ?? 'amber'"
    :available="$product->status->isInStock()"
/>
