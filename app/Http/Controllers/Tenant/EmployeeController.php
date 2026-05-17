<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Hr\Models\Employee;
use App\Http\Controllers\Controller;
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
        return Inertia::render('Employees/Index', [
            'employees' => Employee::query()->with('user')->orderBy('employee_code')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employees/Form', [
            'employee' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_code' => [
                'required', 'string', 'max:64',
                Rule::unique('employees', 'employee_code')->where('tenant_id', tenant_id()),
            ],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        Employee::query()->create($validated);

        return redirect()->route('tenant.employees.index')->with('success', __('Employee created.'));
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('Employees/Form', [
            'employee' => $employee,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'employee_code' => [
                'required', 'string', 'max:64',
                Rule::unique('employees', 'employee_code')->where('tenant_id', tenant_id())->ignore($employee->getKey()),
            ],
            'hire_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $employee->update($validated);

        return redirect()->route('tenant.employees.index')->with('success', __('Employee updated.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('tenant.employees.index')->with('success', __('Employee removed.'));
    }
}
