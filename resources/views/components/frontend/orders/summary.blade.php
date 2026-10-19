@props([
    'order',
])

<x-frontend.cart.summary {{ $attributes }} :summary="['subtotal' => $order->subtotal, 'discount' => $order->discount_amount, 'extraDiscount' => $order->extra_discount_amount, 'extraDiscountPercentage' => $order->extra_discount_percentage, 'total' => $order->total, 'offers' => $order->applied_offers ?? [], 'requiresShipping' => false]" />
