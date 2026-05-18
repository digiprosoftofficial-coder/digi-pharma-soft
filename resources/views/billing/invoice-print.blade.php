<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_no }} — {{ __('platform.billing_print_preview') }}</title>
    <style>
        :root {
            color-scheme: light;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            color: #111827;
            background: #f3f4f6;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgb(0 0 0 / 8%);
        }
        .toolbar a,
        .toolbar button {
            font: inherit;
            font-size: 0.875rem;
            padding: 0.4rem 0.85rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar button.primary,
        .toolbar a.primary {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }
        .preview-wrap {
            max-width: 800px;
            margin: 1.5rem auto 2rem;
            padding: 0 1rem;
        }
        .invoice-sheet {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 4px 24px rgb(0 0 0 / 6%);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #0d6efd;
        }
        .invoice-title {
            margin: 0 0 0.25rem;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .invoice-brand {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .invoice-no {
            margin: 0 0 0.25rem;
            font-size: 1.125rem;
            font-weight: 600;
        }
        .invoice-parties {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        .section-label {
            margin: 0 0 0.35rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 600;
        }
        .party-name {
            margin: 0;
            font-weight: 600;
        }
        .muted { color: #6b7280; margin: 0.15rem 0 0; }
        .small { font-size: 0.8125rem; }
        .text-end { text-align: right; }
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-open { background: #dbeafe; color: #1d4ed8; }
        .status-paid { background: #dcfce7; color: #15803d; }
        .status-void { background: #f3f4f6; color: #4b5563; }
        .status-uncollectible { background: #fee2e2; color: #b91c1c; }
        .invoice-lines {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .invoice-lines th,
        .invoice-lines td {
            border: 1px solid #e5e7eb;
            padding: 0.65rem 0.75rem;
        }
        .invoice-lines thead th {
            background: #f9fafb;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #4b5563;
        }
        .invoice-lines tfoot th {
            background: #f9fafb;
            font-size: 1rem;
        }
        .invoice-footer {
            margin-top: 2rem;
            font-size: 0.8125rem;
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .preview-wrap { margin: 0; padding: 0; max-width: none; }
            .invoice-sheet {
                border: none;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('platform.billing.index') }}">{{ __('platform.billing_back_to_billing') }}</a>
        <button type="button" class="primary" onclick="window.print()">{{ __('platform.billing_print') }}</button>
        <a href="{{ route('platform.billing.invoices.pdf', $invoice) }}" class="primary" target="_blank" rel="noopener">
            {{ __('platform.billing_download_pdf') }}
        </a>
    </div>

    <div class="preview-wrap">
        <article class="invoice-sheet">
            @include('billing.partials.invoice-body')
        </article>
    </div>
</body>
</html>
