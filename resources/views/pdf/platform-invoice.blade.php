<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        .invoice-header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 12px; }
        .invoice-header > div { display: table-cell; vertical-align: top; width: 50%; }
        .invoice-title { font-size: 20px; margin: 0 0 4px; }
        .invoice-no { font-size: 14px; font-weight: bold; margin: 0; }
        .invoice-parties { display: table; width: 100%; margin-bottom: 16px; }
        .invoice-parties > div { display: table-cell; width: 50%; vertical-align: top; }
        .section-label { font-size: 10px; text-transform: uppercase; color: #666; margin: 0 0 4px; }
        .party-name { font-weight: bold; margin: 0; }
        .muted { color: #666; }
        .small { font-size: 10px; }
        .text-end { text-align: right; }
        table.invoice-lines { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.invoice-lines th, table.invoice-lines td { border: 1px solid #ddd; padding: 8px; }
        table.invoice-lines thead th { background: #f5f5f5; }
        .invoice-footer { margin-top: 24px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    @include('billing.partials.invoice-body')
</body>
</html>
