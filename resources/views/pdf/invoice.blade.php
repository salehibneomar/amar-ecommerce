<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoiceNumber }}</title>
    @include('pdf.partials.styles')
</head>
<body>
    <table>
        <tr>
            <td style="vertical-align: top;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" style="height: 48px;">
                @endif
            </td>
            <td class="right" style="vertical-align: top;">
                <div class="title">{{ __('Invoice') }}</div>
                <div class="right muted">{{ $invoiceNumber }}</div>
                <table style="width: auto; margin: 6px 0 0 auto; border-collapse: separate;"><tr><td class="badge">{{ __('Paid') }}</td></tr></table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <div class="label">{{ __('Billed to') }}</div>
                <strong style="color: #000000;">{{ $order->customer_name }}</strong><br>
                {{ $order->customer_email }}<br>
                {{ $order->phone }}
                @if ($order->shipping_address)
                    <br>{{ $order->shipping_address }}
                @endif
            </td>
            <td style="width: 50%;">
                <table>
                    <tr><td class="muted">{{ __('Order ID') }}</td><td class="right">{{ $order->public_order_id }}</td></tr>
                    <tr><td class="muted">{{ __('Order date') }}</td><td class="right">{{ formatDateTime($order->created_at) }}</td></tr>
                    <tr><td class="muted">{{ __('Paid on') }}</td><td class="right">{{ formatDateTime($order->completed_at ?? $order->updated_at) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 28px;">
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th class="right" style="width: 50px;">{{ __('Qty') }}</th>
                <th class="right" style="width: 110px;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <strong style="color: #000000;">{{ $item->product_name_snapshot }}</strong>
                        @if ($item->discount_percentage)
                            <div class="muted" style="font-size: 9px;">{{ __(':percent% off', ['percent' => $item->discount_percentage]) }}</div>
                        @endif
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">
                        @if ($item->original_total > $item->line_total)
                            <span class="muted" style="text-decoration: line-through; font-size: 9px;">{{ formatMoney($item->original_total, $currency.' ') }}</span><br>
                        @endif
                        {{ formatMoney($item->line_total, $currency.' ') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <table class="totals">
                    <tr><td class="muted">{{ __('Subtotal') }}</td><td class="right">{{ formatMoney($order->subtotal, $currency.' ') }}</td></tr>
                    @forelse ($order->applied_offers ?? [] as $offer)
                        <tr><td class="muted">{{ __(':name (:percent% off)', ['name' => $offer['name'], 'percent' => $offer['percentage']]) }}</td><td class="right">- {{ formatMoney($offer['amount'], $currency.' ') }}</td></tr>
                    @empty
                        @if ($order->discount_amount > 0)
                            <tr><td class="muted">{{ __('Offer discount') }}</td><td class="right">- {{ formatMoney($order->discount_amount, $currency.' ') }}</td></tr>
                        @endif
                    @endforelse
                    @if ($order->extra_discount_amount > 0)
                        <tr><td class="muted">{{ __('Extra discount (:percent%)', ['percent' => $order->extra_discount_percentage]) }}</td><td class="right">- {{ formatMoney($order->extra_discount_amount, $currency.' ') }}</td></tr>
                    @endif
                    <tr class="total"><td>{{ __('Total paid') }}</td><td class="right">{{ formatMoney($order->total, $currency.' ') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($order->payment_method)
        <div style="margin-top: 32px;">
            <div class="label">{{ __('Payment details') }}</div>
            <table class="meta" style="width: 60%;">
                <tr><td class="muted" style="width: 40%;">{{ __('Method') }}</td><td>{{ $order->payment_method->label() }}</td></tr>
                <tr><td class="muted">{{ __('Transaction ID') }}</td><td>{{ $order->transaction_id }}</td></tr>
            </table>
        </div>
    @endif

    <div class="footer">
        {{ __('Thank you for your purchase.') }}@if ($supportEmail) {{ __('Questions? Contact :email', ['email' => $supportEmail]) }}@endif
    </div>
</body>
</html>
