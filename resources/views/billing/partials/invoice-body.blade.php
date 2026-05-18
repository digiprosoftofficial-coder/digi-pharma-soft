@php
    /** @var \App\Domain\Billing\Models\PlatformInvoice $invoice */
    $statusLabel = match ($invoice->status) {
        'open' => __('platform.billing_status_open'),
        'paid' => __('platform.billing_status_paid'),
        'void' => __('platform.billing_status_void'),
        'uncollectible' => __('platform.billing_status_uncollectible'),
        default => $invoice->status,
    };
    $description = $invoice->plan?->name ?? __('platform.billing_invoice_line_default');
@endphp

<header class="invoice-header">
    <div>
        <h1 class="invoice-title">{{ __('platform.billing_invoice_pdf_title') }}</h1>
        <p class="invoice-brand">{{ __('platform.brand') }}</p>
    </div>
    <div class="invoice-meta text-end">
        <p class="invoice-no">{{ $invoice->invoice_no }}</p>
        <p class="muted">{{ __('platform.billing_invoice_issued') }}: {{ $invoice->created_at?->format('Y-m-d') }}</p>
    </div>
</header>

<section class="invoice-parties">
    <div>
        <h2 class="section-label">{{ __('platform.billing_bill_to') }}</h2>
        <p class="party-name">{{ $invoice->tenant?->name }}</p>
        <p class="muted">{{ $invoice->tenant?->slug }}</p>
    </div>
    <div class="text-end">
        <h2 class="section-label">{{ __('platform.health_status') }}</h2>
        <p><span class="status-badge status-{{ $invoice->status }}">{{ $statusLabel }}</span></p>
        <p class="muted">{{ __('platform.billing_due_date') }}: {{ $invoice->due_at?->format('Y-m-d') ?? '—' }}</p>
        @if ($invoice->paid_at)
            <p class="muted">{{ __('platform.billing_paid_at') }}: {{ $invoice->paid_at->format('Y-m-d H:i') }}</p>
        @endif
    </div>
</section>

<table class="invoice-lines">
    <thead>
        <tr>
            <th>{{ __('platform.billing_description') }}</th>
            <th class="text-end">{{ __('platform.billing_amount') }}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                {{ $description }}
                @if ($invoice->period_start && $invoice->period_end)
                    <br><span class="muted small">{{ $invoice->period_start->format('Y-m-d') }} — {{ $invoice->period_end->format('Y-m-d') }}</span>
                @endif
            </td>
            <td class="text-end">{{ $amountFormatted }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <th class="text-end">{{ __('platform.billing_total') }}</th>
            <th class="text-end">{{ $amountFormatted }}</th>
        </tr>
    </tfoot>
</table>

<p class="invoice-footer muted">{{ __('platform.billing_invoice_pdf_footer') }}</p>
