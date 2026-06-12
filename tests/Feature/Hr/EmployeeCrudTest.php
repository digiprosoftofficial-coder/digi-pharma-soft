<?php

namespace Tests\Feature\Hr;

use App\Domain\Hr\Models\Attendance;
use App\Domain\Hr\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_employee_with_profile_and_link_user(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $staff = User::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Staff User',
            'email' => 'staff-link@example.com',
            'password' => bcrypt('password'),
        ]);
        $staff->forceFill(['email_verified_at' => now()])->save();
        $staff->assignRole('cashier');

        $this->actingAs($owner)
            ->post('/employees', [
                'employee_code' => 'EMP-100',
                'name' => 'Rahim Uddin',
                'phone' => '01711112222',
                'designation' => 'Cashier',
                'hire_date' => '2024-01-15',
                'salary' => 15000,
                'user_id' => $staff->getKey(),
                'is_active' => true,
            ])
            ->assertRedirect(route('tenant.employees.index'));

        $employee = Employee::query()->where('employee_code', 'EMP-100')->firstOrFail();
        $this->assertSame('Rahim Uddin', $employee->name);
        $this->assertSame($staff->getKey(), $employee->user_id);
    }

    public function test_employee_code_must_be_unique_per_tenant(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        Employee::query()->create([
            'tenant_id' => $owner->tenant_id,
            'employee_code' => 'DUP-1',
            'name' => 'Existing',
        ]);

        $this->actingAs($owner)
            ->post('/employees', [
                'employee_code' => 'DUP-1',
                'name' => 'Another',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('employee_code');
    }

    public function test_can_delete_employee_without_history(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $employee = Employee::query()->create([
            'tenant_id' => $owner->tenant_id,
            'employee_code' => 'DEL-OK',
            'name' => 'Temp Staff',
        ]);

        $this->actingAs($owner)
            ->delete("/employees/{$employee->getKey()}")
            ->assertRedirect(route('tenant.employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $employee->getKey()]);
    }

    public function test_cannot_delete_employee_with_attendance(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $employee = Employee::query()->create([
            'tenant_id' => $owner->tenant_id,
            'employee_code' => 'DEL-BLOCK',
            'name' => 'Clocked Staff',
        ]);

        Attendance::query()->create([
            'tenant_id' => $owner->tenant_id,
            'employee_id' => $employee->getKey(),
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
        ]);

        $this->actingAs($owner)
            ->delete("/employees/{$employee->getKey()}")
            ->assertSessionHasErrors('employee');

        $this->assertDatabaseHas('employees', ['id' => $employee->getKey()]);
    }

    public function test_employee_management_respects_feature_flag(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = $owner->tenant;
        $settings = $tenant->settings ?? [];
        $settings['features'] = ['employee_management' => false];
        $tenant->update(['settings' => $settings]);

        $this->actingAs($owner)
            ->get('/employees')
            ->assertForbidden();
    }
}
