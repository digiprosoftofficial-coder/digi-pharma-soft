<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $purchase->invoice_no }} — {{ __('purchases.print_invoice') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; font-size: 14px; color: #111; background: #f3f4f6; }
        .toolbar { padding: 0.75rem 1rem; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .toolbar a, .toolbar button { font: inherit; font-size: 0.875rem; padding: 0.4rem 0.85rem; border-radius: 0.375rem; border: 1px solid #d1d5db; background: #fff; cursor: pointer; text-decoration: none; color: #111; }
        .toolbar .primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .sheet { max-width: 720px; margin: 1.5rem auto 2rem; background: #fff; padding: 2rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0d6efd; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        h1 { margin: 0; font-size: 1.35rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.4rem 0.5rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th.num, td.num { text-align: right; }
        .totals { margin-left: auto; width: 16rem; }
        .totals td { border: none; padding: 0.2rem 0; }
        .totals .grand td { font-weight: 700; font-size: 1.1rem; border-top: 2px solid #111; padding-top: 0.5rem; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; border: none; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">{{ __('purchases.print') }}</button>
        <a href="{{ route('tenant.purchases.show', $purchase) }}">{{ __('purchases.back_to_purchase') }}</a>
        <a href="{{ route('tenant.purchases.index') }}">{{ __('purchases.back_to_purchases') }}</a>
    </div>
    <div class="sheet">
        <div class="header">
            <div>
                <h1>{{ $storeName }}</h1>
                <p style="margin:0;color:#6b7280">{{ __('purchases.purchase_receipt') }}</p>
            </div>
            <div style="text-align:right">
                <div style="font-weight:700;font-size:1.1rem">{{ $purchase->invoice_no }}</div>
                <div>{{ $purchase->purchased_at?->format('Y-m-d') }}</div>
            </div>
        </div>
        <p>
            <strong>{{ __('purchases.supplier') }}:</strong> {{ $purchase->supplier?->name ?? '—' }}
            @if ($purchase->supplier?->phone) — {{ $purchase->supplier->phone }} @endif
        </p>
        @if ($purchase->notes)
            <p><strong>{{ __('purchases.notes') }}:</strong> {{ $purchase->notes }}</p>
        @endif
        <table>
            <thead>
                <tr>
                    <th>{{ __('purchases.item') }}</th>
                    <th>{{ __('purchases.batch') }}</th>
                    <th class="num">{{ __('purchases.qty') }}</th>
                    <th class="num">{{ __('purchases.stock_added') }}</th>
                    <th class="num">{{ __('purchases.unit_cost') }}</th>
                    <th class="num">{{ __('purchases.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->lines as $line)
                    <tr>
                        <td>
                            {{ $line->product?->name ?? '—' }}
                            @if ($line->product?->sku)
                                <br><small>{{ $line->product->sku }}</small>
                            @endif
                        </td>
                        <td>
                            {{ $line->batch_no }}
                            @if ($line->expiry_date)
                                <br><small>{{ __('purchases.expiry') }}: {{ $line->expiry_date->format('Y-m-d') }}</small>
                            @endif
                            @if ($line->manufactured_at)
                                <br><small>{{ __('purchases.manufactured_at') }}: {{ $line->manufactured_at->format('Y-m-d') }}</small>
                            @endif
                        </td>
                        <td class="num">{{ $line->quantity }} {{ $line->sell_unit }}</td>
                        <td class="num">{{ number_format((float) $line->quantity_base, 2) }} {{ $line->product?->base_unit ?? '' }}</td>
                        <td class="num">{{ number_format((float) $line->unit_cost, 2) }}</td>
                        <td class="num">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="totals">
            <tr><td>{{ __('purchases.subtotal') }}</td><td class="num">{{ number_format((float) $purchase->subtotal, 2) }}</td></tr>
            @if ((float) $purchase->discount > 0)
                <tr><td>{{ __('purchases.discount') }}</td><td class="num">−{{ number_format((float) $purchase->discount, 2) }}</td></tr>
            @endif
            @if ((float) $purchase->tax > 0)
                <tr><td>{{ __('purchases.tax') }}</td><td class="num">{{ number_format((float) $purchase->tax, 2) }}</td></tr>
            @endif
            <tr class="grand"><td>{{ __('purchases.total') }}</td><td class="num">{{ number_format((float) $purchase->total, 2) }}</td></tr>
            <tr><td>{{ __('purchases.paid') }}</td><td class="num">{{ number_format((float) $purchase->paid, 2) }}</td></tr>
            @if ((float) $purchase->due > 0)
                <tr><td>{{ __('purchases.due') }}</td><td class="num">{{ number_format((float) $purchase->due, 2) }}</td></tr>
            @endif
        </table>
        @if ($purchase->payments->isNotEmpty())
            <p style="margin-top:1rem"><strong>{{ __('purchases.payments') }}:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('purchases.date') }}</th>
                        <th>{{ __('purchases.payment_method') }}</th>
                        <th class="num">{{ __('purchases.paid') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('Y-m-d') }}</td>
                            <td>{{ \App\Support\Payments\PaymentMethods::label($payment->method) }}</td>
                            <td class="num">{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
