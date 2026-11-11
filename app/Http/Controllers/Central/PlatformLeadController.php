<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Actions\ConvertLeadToTenantAction;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Services\LeadService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreLeadRequest;
use App\Http\Requests\Platform\UpdateLeadRequest;
use App\Support\Platform\PlatformAssignableStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
            'salesStaff' => PlatformAssignableStaff::optionsFor($request->user()),
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Lead::class);

        return Inertia::render('Platform/Leads/Form', [
            'lead' => null,
            'statuses' => Lead::PIPELINE_STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => PlatformAssignableStaff::optionsFor($request->user()),
            'canAssignToAdmins' => $request->user()->isPlatformSuperAdminRole(),
            'plans' => SubscriptionPlan::query()->active()->orderBy('sort_order')->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $data = $request->validated();
        if (array_key_exists('assigned_to', $data)) {
            $data['assigned_to'] = PlatformAssignableStaff::normalize(
                $request->user(),
                $data['assigned_to'],
            );
        }

        $lead = $this->leads->create($data, $request->user());

        return redirect()
            ->route('platform.leads.show', $lead)
            ->with('success', __('platform.lead_created'));
    }

    public function show(Request $request, Lead $lead): Response
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
            'statuses' => Lead::PIPELINE_STATUSES,
            'allStatuses' => Lead::STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => PlatformAssignableStaff::optionsFor($request->user()),
            'canAssignToAdmins' => $request->user()?->isPlatformSuperAdminRole() ?? false,
            'canConvert' => $request->user()?->can('convert', $lead) ?? false,
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->get(['id', 'name', 'slug', 'trial_days']),
        ]);
    }

    public function edit(Request $request, Lead $lead): Response
    {
        $this->authorize('update', $lead);

        $lead->load('assignee:id,name,email');

        return Inertia::render('Platform/Leads/Form', [
            'lead' => $lead,
            'statuses' => Lead::PIPELINE_STATUSES,
            'sources' => Lead::SOURCES,
            'salesStaff' => PlatformAssignableStaff::optionsFor($request->user()),
            'canAssignToAdmins' => $request->user()->isPlatformSuperAdminRole(),
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validated();
        if (array_key_exists('assigned_to', $data)) {
            $data['assigned_to'] = PlatformAssignableStaff::normalize(
                $request->user(),
                $data['assigned_to'],
                $lead->assigned_to !== null ? (int) $lead->assigned_to : null,
            );
        }

        $this->leads->update($lead, $data, $request->user());

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
        abort_unless($request->user()?->isPlatformSuperAdminRole(), 403);

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

}
