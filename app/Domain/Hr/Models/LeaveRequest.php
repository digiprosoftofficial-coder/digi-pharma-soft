<?php

namespace App\Domain\Hr\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'days', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
