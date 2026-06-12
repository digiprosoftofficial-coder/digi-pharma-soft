<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Hr\Models\Employee;
use App\Domain\Hr\Models\LeaveRequest;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class LeaveRequestController extends Controller
{
    public function index(): Response
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('viewAny', Employee::class);

        return Inertia::render('Hr/LeaveRequests/Index', [
            'requests' => LeaveRequest::query()
                ->with(['employee:id,name,employee_code', 'leaveType:id,name'])
                ->orderByDesc('start_date')
                ->paginate(20),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'leaveTypes' => \App\Domain\Hr\Models\LeaveType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        $validated = $request->validate($this->rules());

        $start = strtotime($validated['start_date']);
        $end = strtotime($validated['end_date']);
        $days = max(1, (int) round(($end - $start) / 86400) + 1);

        LeaveRequest::query()->create([
            ...$validated,
            'days' => $days,
            'status' => 'pending',
        ]);

        return back()->with('success', __('employees.leave_created'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $leaveRequest->update($validated);

        return back()->with('success', __('employees.leave_updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $tenantId = tenant_id();

        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'leave_type_id' => [
                'required', 'integer',
                Rule::exists('leave_types', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
