<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Hr\Models\Employee;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index(): Response
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        return Inertia::render('Employees/Index', [
            'employees' => Employee::query()
                ->with(['user:id,name,email', 'defaultBranch:id,name,code'])
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function show(Employee $employee): Response
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        $employee->load(['user:id,name,email', 'defaultBranch:id,name,code']);

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
        ]);
    }

    public function create(): Response
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        return Inertia::render('Employees/Form', [
            'employee' => null,
            'linkableUsers' => $this->linkableUsers(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active', true);

        Employee::query()->create($validated);

        return redirect()->route('tenant.employees.index')->with('success', __('employees.created'));
    }

    public function edit(Employee $employee): Response
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        return Inertia::render('Employees/Form', [
            'employee' => $employee->load('user:id,name,email'),
            'linkableUsers' => $this->linkableUsers($employee->getKey()),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        $validated = $request->validate($this->rules($employee));
        $validated['is_active'] = $request->boolean('is_active', true);

        $employee->update($validated);

        return redirect()->route('tenant.employees.index')->with('success', __('employees.updated'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        abort_unless(TenantFeatures::employeeManagementEnabled(tenant()), 403);

        if ($employee->attendances()->exists() || $employee->payrollLines()->exists() || $employee->leaveRequests()->exists()) {
            return redirect()
                ->route('tenant.employees.index')
                ->withErrors(['employee' => __('employees.cannot_delete_has_history')]);
        }

        $employee->delete();

        return redirect()->route('tenant.employees.index')->with('success', __('employees.removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Employee $employee = null): array
    {
        $tenantId = tenant_id();

        return [
            'employee_code' => [
                'required', 'string', 'max:64',
                Rule::unique('employees', 'employee_code')->where('tenant_id', $tenantId)->ignore($employee?->getKey()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'designation' => ['nullable', 'string', 'max:128'],
            'is_active' => ['boolean'],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'user_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
                Rule::unique('employees', 'user_id')->where('tenant_id', $tenantId)->ignore($employee?->getKey()),
            ],
            'default_branch_id' => [
                'nullable', 'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
        ];
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    private function linkableUsers(?int $currentEmployeeId = null): array
    {
        $linkedIds = Employee::query()
            ->when($currentEmployeeId, fn ($q) => $q->where('id', '!=', $currentEmployeeId))
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return User::query()
            ->where('tenant_id', tenant_id())
            ->where('is_platform_super_admin', false)
            ->when($currentEmployeeId, function ($q) use ($linkedIds, $currentEmployeeId) {
                $currentUserId = Employee::query()->whereKey($currentEmployeeId)->value('user_id');
                $q->where(function ($inner) use ($linkedIds, $currentUserId) {
                    $inner->whereNotIn('id', $linkedIds);
                    if ($currentUserId) {
                        $inner->orWhere('id', $currentUserId);
                    }
                });
            }, fn ($q) => $q->whereNotIn('id', $linkedIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->all();
    }

    /**
     * @return list<array{id:int,name:string,code:string}>
     */
    private function branchOptions(): array
    {
        if (! TenantFeatures::multiBranchEnabled(tenant())) {
            return [];
        }

        return \App\Domain\Tenant\Models\Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->all();
    }
}
