<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Actions\ConvertLeadToTenantAction;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Services\LeadService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreLeadRequest;
use App\Http\Requests\Platform\UpdateLeadRequest;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

final class PlatformLeadController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Lead::class);

        return Inertia::render('Platform/Leads/Index', [
            'leads' => $this->leads->paginate($request->only(['search', 'status', 'source', 'assigned_to', 'follow_up'])),
            'filters' => $request->only(['search', 'status', 'source', 'assigned_to', 'follow_up']),
            'stats' => $this->leads->dashboardStats(),
            'statuses' => Lead::STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => $this->salesStaff(),
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Lead::class);

        return Inertia::render('Platform/Leads/Form', [
            'lead' => null,
            'statuses' => Lead::STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => $this->salesStaff(),
            'plans' => SubscriptionPlan::query()->active()->orderBy('sort_order')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $lead = $this->leads->create($request->validated(), $request->user());

        return redirect()
            ->route('platform.leads.show', $lead)
            ->with('success', __('platform.lead_created'));
    }

    public function show(Lead $lead): Response
    {
        $this->authorize('view', $lead);

        $lead->load([
            'assignee:id,name,email',
            'interestedPlan:id,name,slug',
            'convertedTenant:id,name,slug',
            'activities.user:id,name',
            'demoRequests',
        ]);

        return Inertia::render('Platform/Leads/Show', [
            'lead' => $lead,
            'statuses' => Lead::STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => $this->salesStaff(),
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->get(['id', 'name', 'slug', 'trial_days']),
        ]);
    }

    public function edit(Lead $lead): Response
    {
        $this->authorize('update', $lead);

        return Inertia::render('Platform/Leads/Form', [
            'lead' => $lead,
            'statuses' => Lead::STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => $this->salesStaff(),
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $this->leads->update($lead, $request->validated(), $request->user());

        return redirect()
            ->route('platform.leads.show', $lead)
            ->with('success', __('platform.lead_updated'));
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()
            ->route('platform.leads.index')
            ->with('success', __('platform.lead_deleted'));
    }

    public function addNote(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->leads->addNote($lead, $data['body'], $request->user());

        return back()->with('success', __('platform.lead_note_added'));
    }

    public function convert(Request $request, Lead $lead, ConvertLeadToTenantAction $convert): RedirectResponse
    {
        $this->authorize('convert', $lead);

        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:64', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'owner_invite' => ['boolean'],
            'billing_interval' => ['nullable', Rule::in(['monthly', 'yearly'])],
            'trial_ends_at' => ['nullable', 'date'],
            'subscription_ends_at' => ['nullable', 'date'],
        ]);

        $tenant = $convert->execute($lead, $request->user(), $data);

        return redirect()
            ->route('platform.tenants.show', $tenant)
            ->with('success', __('platform.lead_converted'));
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    private function salesStaff(): array
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);

        try {
            return User::query()
                ->whereNull('tenant_id')
                ->where(function ($q) {
                    $q->where('is_platform_super_admin', true)
                        ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['super admin', 'sales']));
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->all();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
