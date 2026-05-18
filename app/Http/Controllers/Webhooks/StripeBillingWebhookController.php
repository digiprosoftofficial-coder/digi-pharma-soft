<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Billing\Actions\MarkPlatformInvoiceFailedAction;
use App\Domain\Billing\Actions\MarkPlatformInvoicePaidAction;
use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class StripeBillingWebhookController extends Controller
{
    public function __construct(
        private readonly MarkPlatformInvoicePaidAction $markPaid,
        private readonly MarkPlatformInvoiceFailedAction $markFailed,
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret !== '' && ! $this->verifySignature($payload, (string) $request->header('Stripe-Signature'), $secret)) {
            return response('Invalid signature', 400);
        }

        /** @var array<string, mixed>|null $event */
        $event = json_decode($payload, true);
        if (! is_array($event)) {
            return response('Invalid payload', 400);
        }

        $type = (string) ($event['type'] ?? '');
        $object = $event['data']['object'] ?? null;

        if (! is_array($object)) {
            return response('OK', 200);
        }

        $systemUser = User::query()
            ->where('is_platform_super_admin', true)
            ->orderBy('id')
            ->first();

        if ($systemUser === null) {
            Log::warning('Stripe webhook received but no platform admin exists.');

            return response('OK', 200);
        }

        match ($type) {
            'invoice.paid', 'invoice.payment_succeeded' => $this->handleStripeInvoicePaid($object, $systemUser),
            'invoice.payment_failed' => $this->handleStripeInvoiceFailed($object, $systemUser),
            default => null,
        };

        return response('OK', 200);
    }

    /**
     * @param  array<string, mixed>  $stripeInvoice
     */
    private function handleStripeInvoicePaid(array $stripeInvoice, User $causer): void
    {
        $invoice = $this->resolveInvoice($stripeInvoice);
        if ($invoice === null) {
            return;
        }

        $this->markPaid->execute(
            $invoice,
            $causer,
            (string) ($stripeInvoice['id'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $stripeInvoice
     */
    private function handleStripeInvoiceFailed(array $stripeInvoice, User $causer): void
    {
        $invoice = $this->resolveInvoice($stripeInvoice);
        if ($invoice === null) {
            return;
        }

        $this->markFailed->execute(
            $invoice,
            $causer,
            'Stripe payment failed',
        );
    }

    /**
     * @param  array<string, mixed>  $stripeInvoice
     */
    private function resolveInvoice(array $stripeInvoice): ?PlatformInvoice
    {
        $providerRef = (string) ($stripeInvoice['id'] ?? '');
        if ($providerRef !== '') {
            $existing = PlatformInvoice::query()
                ->where('provider', 'stripe')
                ->where('provider_reference', $providerRef)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $customerId = (string) ($stripeInvoice['customer'] ?? '');
        if ($customerId === '') {
            return null;
        }

        $tenant = Tenant::query()->where('stripe_customer_id', $customerId)->first();
        if ($tenant === null) {
            return null;
        }

        return PlatformInvoice::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('status', PlatformInvoice::STATUS_OPEN)
            ->orderByDesc('id')
            ->first();
    }

    private function verifySignature(string $payload, string $header, string $secret): bool
    {
        if ($header === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_map('trim', explode('=', $part, 2) + [null, null]);
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1' && $value) {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signed = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signed, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
