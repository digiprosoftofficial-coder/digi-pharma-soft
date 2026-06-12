<?php

namespace App\Domain\Hr\Models;

use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends TenantModel
{
    protected $fillable = ['tenant_id', 'name', 'days_per_year', 'is_paid'];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'integer',
            'is_paid' => 'boolean',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
