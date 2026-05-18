<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Actions\CreatePlatformInvoiceAction;
use App\Domain\Billing\Actions\MarkPlatformInvoiceFailedAction;
use App\Domain\Billing\Actions\MarkPlatformInvoicePaidAction;
use App\Domain\Billing\Actions\UpdatePlatformInvoiceAction;
use App\Domain\Billing\Actions\VoidPlatformInvoiceAction;
use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Support\Money\MoneyFormatter;
use App\Support\Platform\PlatformBillingMetrics;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class PlatformBillingController extends Controller
{
    public function __construct(
        private readonly CreatePlatformInvoiceAction $createInvoice,
        private readonly MarkPlatformInvoicePaidAction $markPaid,
        private readonly MarkPlatformInvoiceFailedAction $markFailed,
        private readonly UpdatePlatformInvoiceAction $updateInvoice,
        private readonly VoidPlatformInvoiceAction $voidInvoice,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PlatformInvoice::class);

        $statusFilter = $request->string('status')->toString();

        $invoices = PlatformInvoice::query()
            ->with(['tenant:id,name,slug,billing_status', 'plan:id,name'])
            ->orderByDesc('id')
            ->when($statusFilter !== '' && $statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->paginate(25)
            ->withQueryString();

        $pastDueTenants = Tenant::query()
            ->where('billing_status', 'past_due')
            ->whereNull('suspended_at')
            ->orderBy('grace_period_ends_at')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'grace_period_ends_at', 'payment_failed_at']);

        return Inertia::render('Platform/Billing/Index', [
            'metrics' => PlatformBillingMetrics::snapshot(),
            'invoices' => $invoices->through(fn (PlatformInvoice $inv) => [
                'id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'tenant_id' => $inv->tenant_id,
                'tenant_name' => $inv->tenant?->name,
                'tenant_slug' => $inv->tenant?->slug,
                'subscription_plan_id' => $inv->subscription_plan_id,
                'plan_name' => $inv->plan?->name,
                'amount_cents' => $inv->amount_cents,
                'currency' => $inv->currency,
                'status' => $inv->status,
                'provider' => $inv->provider,
                'due_at' => $inv->due_at?->toIso8601String(),
                'due_at_date' => $inv->due_at?->format('Y-m-d'),
                'paid_at' => $inv->paid_at?->toIso8601String(),
                'created_at' => $inv->created_at?->toIso8601String(),
            ]),
            'pastDueTenants' => $pastDueTenants->map(fn (Tenant $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'grace_period_ends_at' => $t->grace_period_ends_at?->toIso8601String(),
                'payment_failed_at' => $t->payment_failed_at?->toIso8601String(),
            ]),
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(['id', 'name', 'price_cents']),
            'filters' => [
                'status' => $request->string('status')->toString() ?: 'all',
            ],
            'stripe_configured' => filled(config('services.stripe.webhook_secret')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PlatformInvoice::class);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'amount_cents' => ['nullable', 'integer', 'min:1'],
            'due_at' => ['nullable', 'date'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);

        $this->createInvoice->execute($tenant, $validated, $request->user());

        return redirect()
            ->route('platform.billing.index')
            ->with('success', __('platform.billing_invoice_created'));
    }

    public function markPaid(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->markPaid->execute($invoice, $request->user());

        return back()->with('success', __('platform.billing_invoice_paid'));
    }

    public function markFailed(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->markFailed->execute($invoice, $request->user(), $validated['reason'] ?? null);

        return back()->with('success', __('platform.billing_payment_failed'));
    }

    public function update(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'amount_cents' => ['nullable', 'integer', 'min:1'],
            'due_at' => ['nullable', 'date'],
        ]);

        $this->updateInvoice->execute($invoice, $validated, $request->user());

        return back()->with('success', __('platform.billing_invoice_updated'));
    }

    public function destroy(Request $request, PlatformInvoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->voidInvoice->execute($invoice, $request->user(), $validated['reason'] ?? null);

        return back()->with('success', __('platform.billing_invoice_voided'));
    }

    public function printPreview(PlatformInvoice $invoice): \Illuminate\View\View
    {
        $this->authorize('view', $invoice);

        return view('billing.invoice-print', $this->invoiceDocumentData($invoice));
    }

    public function download(PlatformInvoice $invoice): HttpResponse
    {
        $this->authorize('view', $invoice);

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_no).'.pdf';

        return Pdf::loadView('pdf.platform-invoice', $this->invoiceDocumentData($invoice))->download($filename);
    }

    /**
     * @return array{invoice: PlatformInvoice, amountFormatted: string}
     */
    private function invoiceDocumentData(PlatformInvoice $invoice): array
    {
        $invoice->load(['tenant:id,name,slug', 'plan:id,name']);

        return [
            'invoice' => $invoice,
            'amountFormatted' => MoneyFormatter::formatCents($invoice->amount_cents, $invoice->currency),
        ];
    }
}
