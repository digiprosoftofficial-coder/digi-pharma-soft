<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Hr\Models\Employee;
use App\Domain\Hr\Models\LeaveRequest;
use App\Domain\Hr\Models\PayrollRun;
use App\Domain\Hr\Services\PayrollService;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    public function index(): Response
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('viewAny', Employee::class);

        return Inertia::render('Hr/Payroll/Index', [
            'runs' => PayrollRun::query()->orderByDesc('period')->paginate(20),
            'summary' => [
                'active_employees' => Employee::query()->where('is_active', true)->count(),
                'pending_leave' => LeaveRequest::query()->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function show(PayrollRun $payrollRun): Response
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('viewAny', Employee::class);

        $payrollRun->load(['lines.employee:id,name,employee_code']);

        return Inertia::render('Hr/Payroll/Show', [
            'run' => $payrollRun,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        try {
            $run = $this->payroll->generateRun($validated['period']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return redirect()
            ->route('tenant.hr.payroll.show', $run)
            ->with('success', __('employees.payroll_generated'));
    }

    public function finalize(PayrollRun $payrollRun): RedirectResponse
    {
        abort_unless(TenantFeatures::hrPayrollEnabled(tenant()), 403);
        $this->authorize('create', Employee::class);

        try {
            $this->payroll->finalize($payrollRun);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return back()->with('success', __('employees.payroll_finalized'));
    }
}
