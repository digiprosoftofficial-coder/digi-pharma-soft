<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->invoice_no }} — {{ __('sales.print_invoice') }}</title>
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
        .voided { color: #b45309; font-weight: 600; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; border: none; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">{{ __('sales.print') }}</button>
        <a href="{{ route('tenant.sales.index') }}">{{ __('sales.back_to_sales') }}</a>
    </div>
    <div class="sheet">
        <div class="header">
            <div>
                <h1>{{ $storeName }}</h1>
                <p style="margin:0;color:#6b7280">{{ __('sales.sales_invoice') }}</p>
            </div>
            <div style="text-align:right">
                <div style="font-weight:700;font-size:1.1rem">{{ $sale->invoice_no }}</div>
                <div>{{ $sale->sold_at?->format('Y-m-d H:i') }}</div>
                @if ($sale->status === 'voided')
                    <div class="voided">{{ __('sales.status_voided') }}</div>
                @endif
            </div>
        </div>
        @if ($sale->customer)
            <p><strong>{{ __('sales.customer') }}:</strong> {{ $sale->customer->name }}
                @if ($sale->customer->phone) — {{ $sale->customer->phone }} @endif
            </p>
        @endif
        <table>
            <thead>
                <tr>
                    <th>{{ __('sales.item') }}</th>
                    <th>{{ __('sales.batch') }}</th>
                    <th class="num">{{ __('sales.qty') }}</th>
                    <th class="num">{{ __('sales.unit_price') }}</th>
                    <th class="num">{{ __('sales.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->lines as $line)
                    <tr>
                        <td>{{ $line->product?->name ?? '—' }}</td>
                        <td>
                            {{ $line->batch?->batch_no ?? '—' }}
                            @if ($line->batch?->expiry_date)
                                <br><small>{{ $line->batch->expiry_date->format('Y-m-d') }}</small>
                            @endif
                        </td>
                        <td class="num">{{ display_qty($line->quantity) }} {{ $line->sell_unit }}</td>
                        <td class="num">{{ display_money($line->unit_price) }}</td>
                        <td class="num">{{ display_money($line->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="totals">
            <tr><td>{{ __('sales.subtotal') }}</td><td class="num">{{ display_money($sale->subtotal) }}</td></tr>
            @if ((float) $sale->discount > 0)
                <tr><td>{{ __('sales.discount') }}</td><td class="num">−{{ display_money($sale->discount) }}</td></tr>
            @endif
            @if ((float) $sale->tax > 0)
                <tr><td>{{ __('sales.tax') }}</td><td class="num">{{ display_money($sale->tax) }}</td></tr>
            @endif
            <tr class="grand"><td>{{ __('sales.total') }}</td><td class="num">{{ display_money($sale->total) }}</td></tr>
            @if ((float) $sale->round_adjustment != 0)
                <tr>
                    <td>{{ __('sales.round_adjustment') }}</td>
                    <td class="num">{{ ((float) $sale->round_adjustment > 0 ? '+' : '') . display_money($sale->round_adjustment) }}</td>
                </tr>
                <tr style="font-weight:600"><td>{{ __('sales.payable_amount') }}</td><td class="num">{{ display_money($sale->rounded_total) }}</td></tr>
            @endif
            @if ((float) $sale->amount_tendered > 0)
                <tr><td>{{ __('sales.amount_tendered') }}</td><td class="num">{{ display_money($sale->amount_tendered) }}</td></tr>
            @endif
            <tr><td>{{ __('sales.paid') }}</td><td class="num">{{ display_money($sale->paid) }}</td></tr>
            @if ((float) $sale->change_returned > 0)
                <tr><td>{{ __('sales.change_returned') }}</td><td class="num">{{ display_money($sale->change_returned) }}</td></tr>
            @endif
            @if ((float) $sale->due > 0)
                <tr><td>{{ __('sales.due') }}</td><td class="num">{{ display_money($sale->due) }}</td></tr>
            @endif
        </table>
        @if ($sale->payments->isNotEmpty())
            <p style="margin-top:1rem"><strong>{{ __('sales.payments') }}:</strong>
                @foreach ($sale->payments as $p)
                    {{ $p->method }} {{ display_money($p->amount) }}@if (!$loop->last), @endif
                @endforeach
            </p>
        @endif
    </div>
</body>
</html>
