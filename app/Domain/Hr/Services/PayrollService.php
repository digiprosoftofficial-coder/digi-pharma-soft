<?php

namespace App\Domain\Hr\Services;

use App\Domain\Hr\Models\Employee;
use App\Domain\Hr\Models\PayrollLine;
use App\Domain\Hr\Models\PayrollRun;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PayrollService
{
    public function generateRun(string $period): PayrollRun
    {
        return DB::transaction(function () use ($period) {
            if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
                throw new RuntimeException(__('employees.invalid_payroll_period'));
            }

            $run = PayrollRun::query()->firstOrCreate(
                ['period' => $period],
                ['status' => 'draft', 'total_amount' => 0],
            );

            if ($run->status === 'finalized') {
                throw new RuntimeException(__('employees.payroll_already_finalized'));
            }

            $run->lines()->delete();

            $total = 0.0;
            $employees = Employee::query()->where('is_active', true)->get();

            foreach ($employees as $employee) {
                $base = (float) $employee->salary;
                $deductions = 0.0;
                $net = max(0, $base - $deductions);

                PayrollLine::query()->create([
                    'payroll_run_id' => $run->getKey(),
                    'employee_id' => $employee->getKey(),
                    'base_salary' => $base,
                    'deductions' => $deductions,
                    'net_pay' => $net,
                ]);

                $total += $net;
            }

            $run->update(['total_amount' => $total, 'status' => 'draft']);

            return $run->fresh(['lines.employee']);
        });
    }

    public function finalize(PayrollRun $run): PayrollRun
    {
        return DB::transaction(function () use ($run) {
            $run = PayrollRun::query()->whereKey($run->getKey())->lockForUpdate()->firstOrFail();

            if ($run->status === 'finalized') {
                throw new RuntimeException(__('employees.payroll_already_finalized'));
            }

            if ($run->lines()->count() === 0) {
                throw new RuntimeException(__('employees.payroll_no_lines'));
            }

            $run->update([
                'status' => 'finalized',
                'finalized_at' => now(),
            ]);

            return $run->fresh(['lines.employee']);
        });
    }
}
