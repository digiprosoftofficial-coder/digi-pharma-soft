<?php

namespace App\Domain\Hr\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends TenantModel
{
    protected $fillable = ['tenant_id', 'employee_id', 'work_date', 'clock_in', 'clock_out'];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
