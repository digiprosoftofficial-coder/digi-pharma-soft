<?php

namespace App\Domain\Crm\Actions;

use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadActivity;
use App\Domain\Crm\Services\LeadService;
use App\Domain\Tenant\Actions\ProvisionTenantAction;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ConvertLeadToTenantAction
{
    public function __construct(
        private readonly ProvisionTenantAction $provision,
        private readonly LeadService $leads,
    ) {}

    /**
     * @param  array{
     *   slug?: string|null,
     *   subscription_plan_id?: int|null,
     *   trial_ends_at?: string|null,
     *   subscription_ends_at?: string|null,
     *   owner_email?: string|null,
     *   owner_password?: string|null,
     *   owner_invite?: bool,
     *   billing_interval?: string,
     * }  $options
     */
    public function execute(Lead $lead, User $actor, array $options = []): Tenant
    {
        if ($lead->isConverted()) {
            throw ValidationException::withMessages([
                'lead' => __('platform.lead_already_converted'),
            ]);
        }

        return DB::transaction(function () use ($lead, $actor, $options) {
            $slug = $options['slug'] ?? Str::slug($lead->pharmacy_name);
            $slug = Str::lower($slug !== '' ? $slug : 'pharmacy-'.$lead->getKey());

            $base = $slug;
            $i = 1;
            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $planId = (int) ($options['subscription_plan_id'] ?? $lead->interested_plan_id);
            if ($planId < 1) {
                throw ValidationException::withMessages([
                    'subscription_plan_id' => __('platform.lead_plan_required'),
                ]);
            }

            $tenant = $this->provision->execute([
                'name' => $lead->pharmacy_name,
                'slug' => $slug,
                'subscription_plan_id' => $planId,
                'trial_ends_at' => $options['trial_ends_at'] ?? null,
                'subscription_ends_at' => $options['subscription_ends_at'] ?? null,
                'owner_name' => $lead->owner_name,
                'owner_email' => $options['owner_email'] ?? $lead->email,
                'owner_password' => $options['owner_password'] ?? null,
                'owner_invite' => $options['owner_invite'] ?? filled($options['owner_email'] ?? $lead->email),
                'add_owner_later' => empty($options['owner_email'] ?? $lead->email),
            ], $actor);

            $tenant->forceFill(['lead_id' => $lead->getKey()])->save();

            if (! empty($options['billing_interval'])) {
                $sub = $tenant->activeSubscription;
                if ($sub) {
                    $sub->forceFill(['billing_interval' => $options['billing_interval']])->save();
                }
            }

            $this->leads->update($lead, [
                'status' => Lead::STATUS_CONVERTED,
                'converted_tenant_id' => $tenant->getKey(),
            ], $actor);

            $this->leads->addNote(
                $lead,
                'Lead converted to pharmacy '.$tenant->slug.'.',
                $actor,
                LeadActivity::TYPE_CONVERTED,
            );

            return $tenant->fresh(['activeSubscription.plan', 'users']);
        });
    }
}
