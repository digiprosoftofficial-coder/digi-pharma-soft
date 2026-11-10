<?php

namespace App\Domain\Platform\Services;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Services\PlatformPaymentService;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Services\LeadService;
use App\Domain\Platform\Models\OtpVerification;
use App\Domain\Tenant\Actions\ProvisionTenantAction;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SelfServiceRegistrationService
{
    public function __construct(
        private readonly ProvisionTenantAction $provision,
        private readonly PlatformPaymentService $payments,
        private readonly LeadService $leads,
        private readonly OtpService $otp,
    ) {}

    public function sendOtp(string $email): OtpVerification
    {
        if (! PlatformSettings::selfServiceEnabled()) {
            throw ValidationException::withMessages([
                'owner_email' => __('platform.self_service_disabled'),
            ]);
        }

        [$row] = $this->otp->send($email, OtpVerification::PURPOSE_REGISTRATION);

        return $row;
    }

    public function verifyOtp(string $email, string $code): OtpVerification
    {
        return $this->otp->verify($email, $code, OtpVerification::PURPOSE_REGISTRATION);
    }

    /**
     * @param  array{
     *   pharmacy_name: string,
     *   slug?: string|null,
     *   owner_name: string,
     *   owner_email: string,
     *   owner_password: string,
     *   phone: string,
     *   otp_session_token: string,
     *   subscription_plan_id: int,
     *   billing_interval: string,
     *   start_trial?: bool,
     *   payment_method?: string|null,
     *   transaction_reference?: string|null,
     *   branch_count?: int,
     *   address?: string|null,
     * }  $data
     * @return array{tenant: Tenant, payment: \App\Domain\Billing\Models\PlatformPayment|null}
     */
    public function register(array $data): array
    {
        if (! PlatformSettings::selfServiceEnabled()) {
            throw ValidationException::withMessages([
                'pharmacy_name' => __('platform.self_service_disabled'),
            ]);
        }

        $otp = OtpVerification::query()
            ->where('session_token', $data['otp_session_token'])
            ->where('email', Str::lower(trim($data['owner_email'])))
            ->where('purpose', OtpVerification::PURPOSE_REGISTRATION)
            ->first();

        if (! $otp || ! $otp->isVerified() || $otp->isExpired()) {
            throw ValidationException::withMessages([
                'otp_session_token' => __('platform.otp_invalid'),
            ]);
        }

        $plan = SubscriptionPlan::query()->active()->findOrFail($data['subscription_plan_id']);
        $interval = $data['billing_interval'] === 'yearly' ? 'yearly' : 'monthly';
        $startTrial = ! empty($data['start_trial']) && $plan->trial_days > 0;

        return DB::transaction(function () use ($data, $plan, $interval, $startTrial) {
            $slug = Str::lower($data['slug'] ?? Str::slug($data['pharmacy_name']));
            if ($slug === '') {
                $slug = 'pharmacy-'.Str::random(6);
            }

            $base = $slug;
            $i = 1;
            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $systemUser = User::query()
                ->whereNull('tenant_id')
                ->where('is_platform_super_admin', true)
                ->orderBy('id')
                ->first()
                ?? User::query()->whereNull('tenant_id')->orderBy('id')->firstOrFail();

            if ($startTrial) {
                $trialEnds = now()->addDays((int) $plan->trial_days);
                // Equal ends_at keeps access trial-only until a paid period is activated.
                $subscriptionEnds = $trialEnds->copy();
            } else {
                // Block pharmacy access until payment is verified (invoice paid extends subscription_ends_at).
                $trialEnds = now()->subMinute();
                $subscriptionEnds = now()->subMinute();
            }

            $lead = $this->leads->create([
                'pharmacy_name' => $data['pharmacy_name'],
                'owner_name' => $data['owner_name'],
                'phone' => $data['phone'],
                'whatsapp' => $data['phone'],
                'email' => $data['owner_email'],
                'branch_count' => $data['branch_count'] ?? 1,
                'address' => $data['address'] ?? null,
                'source' => 'website',
                'interested_plan_id' => $plan->getKey(),
                'status' => $startTrial ? Lead::STATUS_TRIAL_STARTED : Lead::STATUS_NEW,
            ], $systemUser);

            $tenant = $this->provision->execute([
                'name' => $data['pharmacy_name'],
                'slug' => $slug,
                'subscription_plan_id' => $plan->getKey(),
                'trial_ends_at' => $trialEnds->toDateTimeString(),
                'subscription_ends_at' => $subscriptionEnds->toDateTimeString(),
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_password' => $data['owner_password'],
                'owner_invite' => false,
            ], $systemUser);

            $tenant->forceFill([
                'lead_id' => $lead->getKey(),
                'billing_status' => $startTrial ? 'trialing' : 'past_due',
            ])->save();

            $tenant->activeSubscription?->forceFill([
                'billing_interval' => $interval,
            ])->save();

            $this->leads->update($lead, [
                'converted_tenant_id' => $tenant->getKey(),
                'status' => $startTrial ? Lead::STATUS_TRIAL_STARTED : Lead::STATUS_CONVERTED,
            ], $systemUser);

            $settings = $tenant->settings ?? [];
            $settings['onboarding'] = [
                'steps' => [
                    'profile' => false,
                    'branch' => true,
                    'staff' => false,
                    'categories' => false,
                    'products' => false,
                    'suppliers' => false,
                    'opening_stock' => false,
                    'first_sale' => false,
                ],
                'started_at' => now()->toIso8601String(),
                'completed_at' => null,
            ];
            $tenant->forceFill(['settings' => $settings])->save();

            $payment = null;
            if (! $startTrial) {
                if (empty($data['payment_method']) || empty($data['transaction_reference'])) {
                    throw ValidationException::withMessages([
                        'transaction_reference' => __('platform.payment_required'),
                    ]);
                }

                $payment = $this->payments->submit($tenant, [
                    'method' => $data['payment_method'],
                    'transaction_reference' => $data['transaction_reference'],
                    'billing_interval' => $interval,
                    'subscription_plan_id' => $plan->getKey(),
                    'amount_cents' => $plan->priceForInterval($interval),
                ], $tenant->users()->first());

                if (PlatformSettings::selfServiceRequiresApproval()) {
                    // stays pending / past_due until admin verifies
                }
            }

            return ['tenant' => $tenant->fresh(['users', 'activeSubscription.plan']), 'payment' => $payment];
        });
    }
}
