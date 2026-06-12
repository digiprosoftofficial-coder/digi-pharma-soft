<?php

namespace App\Domain\Hr\Services;

use App\Domain\Hr\Models\Attendance;
use App\Domain\Hr\Models\Employee;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AttendanceService
{
    public function clockIn(Employee $employee): Attendance
    {
        return DB::transaction(function () use ($employee) {
            $today = now()->toDateString();
            $row = Attendance::query()
                ->where('employee_id', $employee->getKey())
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            if ($row && $row->clock_in !== null) {
                throw new RuntimeException(__('employees.already_clocked_in'));
            }

            if ($row) {
                $row->update(['clock_in' => now()]);
                return $row->fresh();
            }

            return Attendance::query()->create([
                'employee_id' => $employee->getKey(),
                'work_date' => $today,
                'clock_in' => now(),
            ]);
        });
    }

    public function clockOut(Employee $employee): Attendance
    {
        return DB::transaction(function () use ($employee) {
            $today = now()->toDateString();
            $row = Attendance::query()
                ->where('employee_id', $employee->getKey())
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            if (! $row || $row->clock_in === null) {
                throw new RuntimeException(__('employees.clock_in_required'));
            }

            if ($row->clock_out !== null) {
                throw new RuntimeException(__('employees.already_clocked_out'));
            }

            $row->update(['clock_out' => now()]);

            return $row->fresh();
        });
    }

    public function employeeForUser(?int $userId): ?Employee
    {
        if ($userId === null) {
            return null;
        }

        return Employee::query()->where('user_id', $userId)->first();
    }
}
