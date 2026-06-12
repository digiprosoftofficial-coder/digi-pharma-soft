<?php

namespace App\Domain\Hr\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'payroll_run_id', 'employee_id', 'base_salary', 'deductions', 'net_pay',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:4',
            'deductions' => 'decimal:4',
            'net_pay' => 'decimal:4',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
