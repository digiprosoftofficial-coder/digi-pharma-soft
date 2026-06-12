<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Hr\Models\Attendance;
use App\Domain\Hr\Models\Employee;
use App\Domain\Hr\Services\AttendanceService;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(Request $request): Response
    {
        abort_unless(TenantFeatures::attendanceEnabled(tenant()), 403);
        abort_unless($this->canAccessAttendance($request), 403);

        $date = $request->string('date')->toString() ?: now()->toDateString();

        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code']);

        $rows = Attendance::query()
            ->with('employee:id,name,employee_code')
            ->where('work_date', $date)
            ->orderByDesc('clock_in')
            ->paginate(30)
            ->withQueryString();

        $myEmployee = $this->attendance->employeeForUser($request->user()?->getKey());

        return Inertia::render('Attendance/Index', [
            'date' => $date,
            'attendances' => $rows,
            'employees' => $employees,
            'myEmployee' => $myEmployee,
            'canManageOthers' => $request->user()?->can('employees.manage') ?? false,
        ]);
    }

    public function clockIn(Request $request, ?Employee $employee = null): RedirectResponse
    {
        abort_unless(TenantFeatures::attendanceEnabled(tenant()), 403);

        $target = $this->resolveEmployee($request, $employee);
        $this->authorizeAttendance($request, $target, $employee !== null);

        try {
            $this->attendance->clockIn($target);
        } catch (RuntimeException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()]);
        }

        return back()->with('success', __('employees.clock_in'));
    }

    public function clockOut(Request $request, ?Employee $employee = null): RedirectResponse
    {
        abort_unless(TenantFeatures::attendanceEnabled(tenant()), 403);

        $target = $this->resolveEmployee($request, $employee);
        $this->authorizeAttendance($request, $target, $employee !== null);

        try {
            $this->attendance->clockOut($target);
        } catch (RuntimeException $e) {
            return back()->withErrors(['attendance' => $e->getMessage()]);
        }

        return back()->with('success', __('employees.clock_out'));
    }

    private function resolveEmployee(Request $request, ?Employee $employee): Employee
    {
        if ($employee !== null) {
            return $employee;
        }

        $mine = $this->attendance->employeeForUser($request->user()?->getKey());
        abort_if($mine === null, 403, __('employees.no_employee_linked'));

        return $mine;
    }

    private function canAccessAttendance(Request $request): bool
    {
        $user = $request->user();
        if ($user === null) {
            return false;
        }

        if ($user->can('employees.view')) {
            return true;
        }

        return $this->attendance->employeeForUser($user->getKey()) !== null;
    }

    private function authorizeAttendance(Request $request, Employee $employee, bool $managingOther): void
    {
        $user = $request->user();
        abort_if($user === null, 403);

        if ($managingOther) {
            abort_unless($user->can('employees.manage'), 403);
            abort_unless((int) $employee->tenant_id === (int) $user->tenant_id, 403);

            return;
        }

        abort_unless((int) $employee->user_id === (int) $user->getKey(), 403);
    }
}
