<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;

class StripeBillingWebhookTest extends TestCase
{
    public function test_rejects_webhooks_when_secret_is_not_configured(): void
    {
        config(['services.stripe.webhook_secret' => '']);

        $this->postJson('/api/webhooks/stripe/billing', [
            'type' => 'invoice.paid',
            'data' => ['object' => ['id' => 'in_test']],
        ])->assertStatus(503);
    }

    public function test_rejects_webhooks_with_an_invalid_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

        $this->withHeaders(['Stripe-Signature' => 't='.time().',v1=deadbeef'])
            ->postJson('/api/webhooks/stripe/billing', [
                'type' => 'invoice.paid',
                'data' => ['object' => ['id' => 'in_test']],
            ])
            ->assertStatus(400);
    }

    public function test_accepts_a_valid_signed_payload(): void
    {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook_secret' => $secret]);

        $payload = json_encode([
            'type' => 'invoice.paid',
            'data' => ['object' => ['id' => 'in_unknown', 'customer' => 'cus_unknown']],
        ], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        $this->call(
            'POST',
            '/api/webhooks/stripe/billing',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            ],
            $payload,
        )->assertOk();
    }
}
