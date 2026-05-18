<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Billing\Models\TenantSubscription;
use App\Domain\Platform\Models\CatalogTemplate;
use App\Domain\Platform\Models\Reseller;
use App\Domain\Tenant\Actions\AttachTenantOwnerAction;
use App\Domain\Tenant\Actions\ProvisionTenantAction;
use App\Domain\Tenant\Actions\SuspendTenantAction;
use App\Domain\Tenant\Actions\UnsuspendTenantAction;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformSettings;
use App\Support\Tenant\TenantPresenter;
use App\Support\Tenant\TenantStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

final class PlatformTenantController extends Controller
{
    public function __construct(
        private readonly ProvisionTenantAction $provision,
        private readonly AttachTenantOwnerAction $attachOwner,
        private readonly SuspendTenantAction $suspend,
        private readonly UnsuspendTenantAction $unsuspend,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $query = Tenant::query()
            ->withCount('users')
            ->with(['activeSubscription.plan', 'reseller']);

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        TenantStatus::applyFilter($query, $request->string('status')->toString() ?: null);

        $tenants = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return Inertia::render('Platform/Tenants/Index', [
            'tenants' => $tenants->through(fn (Tenant $t) => TenantPresenter::listItem($t)),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString() ?: 'all',
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tenant::class);

        return Inertia::render('Platform/Tenants/Create', [
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(['id', 'name', 'slug', 'trial_days', 'price_cents']),
            'resellers' => Reseller::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'default_trial_days' => PlatformSettings::defaultTrialDays(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Tenant::class);

        $validated = $this->validateProvision($request);

        $tenant = $this->provision->execute($validated, $request->user());

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', __('platform.tenant_created'));
    }

    public function show(Request $request, Tenant $tenant): Response
    {
        $this->authorize('view', $tenant);

        $tenant->load([
            'users',
            'reseller',
            'activeSubscription.plan',
            'subscriptions' => fn ($q) => $q->with('plan')->orderByDesc('starts_at'),
            'platformInvoices' => fn ($q) => $q->orderByDesc('id')->limit(5),
        ]);

        $activities = Activity::query()
            ->where(function ($q) use ($tenant) {
                $q->where('tenant_id', $tenant->getKey())
                    ->orWhere(fn ($q2) => $q2
                        ->where('subject_type', Tenant::class)
                        ->where('subject_id', $tenant->getKey()));
            })
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['description', 'event', 'created_at', 'properties']);

        return Inertia::render('Platform/Tenants/Show', [
            'tenant' => TenantPresenter::detail($tenant),
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(['id', 'name']),
            'activities' => $activities,
            'canAddOwner' => ! $this->attachOwner->tenantHasOwner($tenant),
            'canImpersonate' => $tenant->users->isNotEmpty(),
            'canResendOwnerInvite' => $this->canResendOwnerInvite($tenant),
            'ownerInvitePending' => $this->ownerInvitePending($tenant),
            'canExportData' => $request->user()?->can('exportData', $tenant) ?? false,
            'canPurgeData' => $request->user()?->can('purgeData', $tenant) ?? false,
            'tenantInvoices' => $tenant->platformInvoices->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'amount_cents' => $inv->amount_cents,
                'currency' => $inv->currency,
                'status' => $inv->status,
                'due_at' => $inv->due_at?->toIso8601String(),
                'paid_at' => $inv->paid_at?->toIso8601String(),
            ]),
            'billingStatus' => $tenant->billing_status,
            'gracePeriodEndsAt' => $tenant->grace_period_ends_at?->toIso8601String(),
            'catalogTemplates' => CatalogTemplate::query()
                ->where('is_published', true)
                ->withCount('items')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function storeOwner(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('attachOwner', $tenant);

        $invite = $request->boolean('owner_invite');

        $validated = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_invite' => ['boolean'],
            'owner_password' => [
                Rule::requiredIf(! $invite),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $this->attachOwner->execute($tenant, [
            'name' => $validated['owner_name'],
            'email' => $validated['owner_email'],
            'password' => $validated['owner_password'] ?? null,
        ], $request->user(), $invite);

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', $invite ? __('platform.owner_invited') : __('platform.owner_added'));
    }

    public function resendOwnerInvite(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('attachOwner', $tenant);

        $this->attachOwner->resendInvitation($tenant, $request->user());

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', __('platform.owner_invite_resent'));
    }

    public function edit(Tenant $tenant): Response
    {
        $this->authorize('update', $tenant);

        $tenant->load(['activeSubscription.plan']);

        return Inertia::render('Platform/Tenants/Edit', [
            'tenant' => TenantPresenter::detail($tenant),
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(['id', 'name', 'trial_days']),
            'resellers' => Reseller::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'reseller_id' => ['nullable', 'integer', 'exists:resellers,id'],
        ]);

        $tenant->name = $validated['name'];
        $tenant->reseller_id = $validated['reseller_id'] ?? null;
        $tenant->internal_notes = $validated['internal_notes'] ?? null;
        $tenant->is_active = $request->boolean('is_active', true);
        $tenant->trial_ends_at = $validated['trial_ends_at'] ?? $tenant->trial_ends_at;
        $tenant->subscription_ends_at = $validated['subscription_ends_at'] ?? $tenant->subscription_ends_at;
        $tenant->save();

        if (! empty($validated['subscription_plan_id'])) {
            $sub = $tenant->activeSubscription;
            if ($sub) {
                $sub->subscription_plan_id = $validated['subscription_plan_id'];
                $sub->ends_at = $tenant->subscription_ends_at;
                $sub->save();
            } else {
                TenantSubscription::query()->create([
                    'tenant_id' => $tenant->getKey(),
                    'subscription_plan_id' => $validated['subscription_plan_id'],
                    'starts_at' => now(),
                    'ends_at' => $tenant->subscription_ends_at,
                    'status' => 'active',
                ]);
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->event('tenant.updated')
            ->log('Pharmacy updated');

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', __('platform.tenant_updated'));
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('suspend', $tenant);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->suspend->execute($tenant, $request->user(), $validated['reason'] ?? null);

        return back()->with('success', __('platform.tenant_suspended'));
    }

    public function unsuspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('suspend', $tenant);

        $this->unsuspend->execute($tenant, $request->user());

        return back()->with('success', __('platform.tenant_unsuspended'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProvision(Request $request): array
    {
        $addOwnerLater = $request->boolean('add_owner_later');
        $ownerInvite = $request->boolean('owner_invite');

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'reseller_id' => ['nullable', 'integer', 'exists:resellers,id'],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
            'add_owner_later' => ['boolean'],
            'owner_invite' => ['boolean'],
            'owner_name' => [Rule::requiredIf(! $addOwnerLater), 'nullable', 'string', 'max:255'],
            'owner_email' => [
                Rule::requiredIf(! $addOwnerLater),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'owner_password' => [
                Rule::requiredIf(! $addOwnerLater && ! $ownerInvite),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);
    }

    private function canResendOwnerInvite(Tenant $tenant): bool
    {
        $owner = $this->attachOwner->findOwner($tenant);

        return $owner !== null && $owner->email_verified_at === null;
    }

    /**
     * @return array{email: string, name: string}|null
     */
    private function ownerInvitePending(Tenant $tenant): ?array
    {
        $owner = $this->attachOwner->findOwner($tenant);

        if ($owner === null || $owner->email_verified_at !== null) {
            return null;
        }

        return [
            'email' => $owner->email,
            'name' => $owner->name,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'all', 'label' => __('platform.filter_all')],
            ['value' => TenantStatus::RUNNING, 'label' => __('platform.status_running')],
            ['value' => TenantStatus::TRIAL, 'label' => __('platform.status_trial')],
            ['value' => TenantStatus::EXPIRING, 'label' => __('platform.status_expiring')],
            ['value' => TenantStatus::EXPIRED, 'label' => __('platform.status_expired')],
            ['value' => TenantStatus::SUSPENDED, 'label' => __('platform.status_suspended')],
            ['value' => TenantStatus::INACTIVE, 'label' => __('platform.status_inactive')],
        ];
    }
}
