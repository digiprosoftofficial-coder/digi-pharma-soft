<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Hr\Models\Employee;
use App\Domain\Hr\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('viewAny', Employee::class);

        return Inertia::render('Hr/LeaveTypes/Index', [
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        $validated = $request->validate($this->rules());

        LeaveType::query()->create($validated);

        return back()->with('success', __('employees.leave_type_saved'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        $leaveType->update($request->validate($this->rules($leaveType)));

        return back()->with('success', __('employees.leave_type_saved'));
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        if ($leaveType->leaveRequests()->exists()) {
            return back()->withErrors(['leave_type' => __('employees.cannot_delete_has_history')]);
        }

        $leaveType->delete();

        return back()->with('success', __('employees.leave_type_removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?LeaveType $leaveType = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:128',
                Rule::unique('leave_types', 'name')->where('tenant_id', tenant_id())->ignore($leaveType?->getKey()),
            ],
            'days_per_year' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['boolean'],
        ];
    }
}
