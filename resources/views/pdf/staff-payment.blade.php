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
                <div class="title">{{ __('Payment invoice') }}</div>
                <div class="right muted">{{ $invoiceNumber }}</div>
                <table style="width: auto; margin: 6px 0 0 auto; border-collapse: separate;"><tr><td class="badge">{{ __('Paid') }}</td></tr></table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="meta">
        <tr>
            <td style="width: 50%;">
                <div class="label">{{ __('Paid to') }}</div>
                <strong style="color: #000000;">{{ $payment->user->name }}</strong><br>
                {{ $payment->user->email }}
            </td>
            <td style="width: 50%;">
                <table>
                    <tr><td class="muted">{{ __('Period') }}</td><td class="right">{{ $payment->periodLabel() }}</td></tr>
                    <tr><td class="muted">{{ __('Paid on') }}</td><td class="right">{{ formatDateTime($payment->paid_at) ?: appEmptyValue() }}</td></tr>
                    <tr><td class="muted">{{ __('Paid by') }}</td><td class="right">{{ $payment->paidBy->name ?? appEmptyValue() }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 28px;">
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th class="right" style="width: 110px;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong style="color: #000000;">{{ $payment->compensation_type_snapshot->label() }}</strong>
                    <div class="muted" style="font-size: 9px;">{{ $payment->basisLabel($currency.' ') }}</div>
                </td>
                <td class="right">{{ formatMoney($payment->amount, $currency.' ') }}</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top: 12px;">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <table class="totals">
                    <tr class="total"><td>{{ __('Total paid') }}</td><td class="right">{{ formatMoney($payment->amount, $currency.' ') }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($payment->notes)
        <div style="margin-top: 32px;">
            <div class="label">{{ __('Notes') }}</div>
            <div>{{ $payment->notes }}</div>
        </div>
    @endif

    <div class="footer">{{ $shopName }}</div>
</body>
</html>
