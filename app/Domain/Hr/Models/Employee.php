<?php

namespace App\Domain\Hr\Models;

use App\Models\User;
use App\Support\Models\TenantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends TenantModel
{
    protected $fillable = ['tenant_id', 'user_id', 'employee_code', 'hire_date', 'salary'];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'salary' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
