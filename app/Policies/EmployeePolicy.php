<?php

namespace App\Policies;

use App\Domain\Hr\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view') && (int) $user->tenant_id === (int) $employee->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.manage') && (int) $user->tenant_id === (int) $employee->tenant_id;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.manage') && (int) $user->tenant_id === (int) $employee->tenant_id;
    }
}
