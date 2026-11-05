<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Actions\CreatePlatformInvoiceAction;
use App\Domain\Billing\Actions\MarkPlatformInvoiceFailedAction;
use App\Domain\Billing\Actions\MarkPlatformInvoicePaidAction;
use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Billing\Models\PlatformPayment;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformEventNotifier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PlatformPaymentService
{
    public function __construct(
        private readonly CreatePlatformInvoiceAction $createInvoice,
        private readonly MarkPlatformInvoicePaidAction $markPaid,
        private readonly MarkPlatformInvoiceFailedAction $markFailed,
        private readonly PlatformEventNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PlatformPayment::query()
            ->with(['tenant:id,name,slug', 'invoice', 'submitter:id,name', 'verifier:id,name'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($tq) => $tq->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{
     *   method: string,
     *   amount_cents?: int|null,
     *   transaction_reference?: string|null,
     *   notes?: string|null,
     *   billing_interval?: string,
     *   subscription_plan_id?: int|null,
     *   idempotency_key?: string|null,
     * }  $data
     */
    public function submit(Tenant $tenant, array $data, ?User $submitter = null): PlatformPayment
    {
        if (! empty($data['idempotency_key'])) {
            $existing = PlatformPayment::query()
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $reference = isset($data['transaction_reference'])
            ? trim((string) $data['transaction_reference'])
            : null;

        if ($reference) {
            $dup = PlatformPayment::query()
                ->where('method', $data['method'])
                ->where('transaction_reference', $reference)
                ->whereIn('status', [PlatformPayment::STATUS_PENDING, PlatformPayment::STATUS_VERIFIED])
                ->exists();

            if ($dup) {
                throw ValidationException::withMessages([
                    'transaction_reference' => __('platform.payment_duplicate_reference'),
                ]);
            }
        }

        return DB::transaction(function () use ($tenant, $data, $submitter, $reference) {
            $interval = $data['billing_interval'] ?? 'monthly';
            $planId = $data['subscription_plan_id']
                ?? $tenant->activeSubscription?->subscription_plan_id;

            $plan = $planId ? SubscriptionPlan::query()->find($planId) : null;
            $amount = (int) ($data['amount_cents'] ?? ($plan?->priceForInterval($interval) ?? 0));

            $invoice = $this->createInvoice->execute($tenant, [
                'subscription_plan_id' => $plan?->getKey(),
                'amount_cents' => $amount,
                'currency' => $tenant->currency(),
                'provider' => $data['method'],
                'provider_reference' => $reference,
                'billing_interval' => $interval,
                'period_start' => now(),
                'period_end' => $interval === 'yearly' ? now()->addYear() : now()->addMonth(),
                'due_at' => now()->addDays(3),
            ], $submitter ?? User::query()->whereNull('tenant_id')->first());

            if ($interval) {
                $invoice->forceFill(['billing_interval' => $interval])->save();
            }

            $payment = PlatformPayment::query()->create([
                'tenant_id' => $tenant->getKey(),
                'platform_invoice_id' => $invoice->getKey(),
                'method' => $data['method'],
                'amount_cents' => $amount,
                'currency' => $tenant->currency(),
                'transaction_reference' => $reference,
                'status' => PlatformPayment::STATUS_PENDING,
                'idempotency_key' => $data['idempotency_key'] ?? (string) Str::uuid(),
                'submitted_by' => $submitter?->getKey(),
                'notes' => $data['notes'] ?? null,
                'meta' => [
                    'billing_interval' => $interval,
                    'subscription_plan_id' => $plan?->getKey(),
                ],
            ]);

            $this->notifier->notifyOperators(
                'payment.received',
                'Payment received',
                "{$tenant->name} submitted {$data['method']} payment ({$reference}).",
                ['payment_id' => $payment->getKey(), 'tenant_id' => $tenant->getKey()],
            );

            return $payment;
        });
    }

    public function verify(PlatformPayment $payment, User $actor, ?string $notes = null): PlatformPayment
    {
        if ($payment->status === PlatformPayment::STATUS_VERIFIED) {
            return $payment;
        }

        if ($payment->status !== PlatformPayment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'payment' => __('platform.payment_not_pending'),
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $notes) {
            $locked = PlatformPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== PlatformPayment::STATUS_PENDING) {
                return $locked;
            }

            $invoice = $locked->invoice;
            if ($invoice && $invoice->isOpen()) {
                $this->markPaid->execute($invoice, $actor, $locked->transaction_reference);
            }

            $interval = $locked->meta['billing_interval'] ?? $invoice?->billing_interval;
            if ($interval && $locked->tenant?->activeSubscription) {
                $locked->tenant->activeSubscription->forceFill([
                    'billing_interval' => $interval,
                ])->save();
            }

            $locked->forceFill([
                'status' => PlatformPayment::STATUS_VERIFIED,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
                'notes' => $notes ?: $locked->notes,
            ])->save();

            activity()
                ->causedBy($actor)
                ->performedOn($locked)
                ->withProperties(['invoice_id' => $locked->platform_invoice_id])
                ->event('billing.payment_verified')
                ->log('Platform payment verified');

            return $locked->fresh(['tenant', 'invoice', 'verifier']);
        });
    }

    public function reject(PlatformPayment $payment, User $actor, ?string $notes = null): PlatformPayment
    {
        if ($payment->status !== PlatformPayment::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'payment' => __('platform.payment_not_pending'),
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $notes) {
            $locked = PlatformPayment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== PlatformPayment::STATUS_PENDING) {
                return $locked;
            }

            $invoice = $locked->invoice;
            if ($invoice && $invoice->isOpen()) {
                $this->markFailed->execute($invoice, $actor);
            }

            $locked->forceFill([
                'status' => PlatformPayment::STATUS_REJECTED,
                'verified_by' => $actor->getKey(),
                'rejected_at' => now(),
                'notes' => $notes ?: $locked->notes,
            ])->save();

            activity()
                ->causedBy($actor)
                ->performedOn($locked)
                ->event('billing.payment_rejected')
                ->log('Platform payment rejected');

            return $locked->fresh(['tenant', 'invoice', 'verifier']);
        });
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        return [
            'pending' => PlatformPayment::query()->where('status', PlatformPayment::STATUS_PENDING)->count(),
            'verified' => PlatformPayment::query()->where('status', PlatformPayment::STATUS_VERIFIED)->count(),
            'rejected' => PlatformPayment::query()->where('status', PlatformPayment::STATUS_REJECTED)->count(),
        ];
    }
}
