<?php

namespace Tests\Feature\Hr;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Hr\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_is_gated_by_plan_feature(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get('/attendance')
            ->assertForbidden();
    }

    public function test_linked_employee_can_clock_in_and_out(): void
    {
        $this->seed();
        $this->enableAttendance();

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $staff = User::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Attendance Staff',
            'email' => 'staff-att@example.com',
            'password' => bcrypt('password'),
        ]);
        $staff->forceFill(['email_verified_at' => now()])->save();
        $staff->assignRole('cashier');
        $employee = Employee::query()->create([
            'tenant_id' => $staff->tenant_id,
            'user_id' => $staff->getKey(),
            'employee_code' => 'ATT-1',
            'name' => $staff->name,
        ]);

        $this->actingAs($staff)
            ->post('/attendance/clock-in')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->getKey(),
            'work_date' => now()->toDateString(),
        ]);

        $this->actingAs($staff)
            ->post('/attendance/clock-out')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(
            Employee::query()->find($employee->getKey())?->attendances()->first()?->clock_out
        );
    }

    public function test_cannot_clock_out_without_clock_in(): void
    {
        $this->seed();
        $this->enableAttendance();

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $staff = User::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Attendance Staff 2',
            'email' => 'staff-att2@example.com',
            'password' => bcrypt('password'),
        ]);
        $staff->forceFill(['email_verified_at' => now()])->save();
        $staff->assignRole('cashier');
        Employee::query()->create([
            'tenant_id' => $staff->tenant_id,
            'user_id' => $staff->getKey(),
            'employee_code' => 'ATT-2',
            'name' => $staff->name,
        ]);

        $this->actingAs($staff)
            ->post('/attendance/clock-out')
            ->assertSessionHasErrors('attendance');
    }

    private function enableAttendance(): void
    {
        $plan = SubscriptionPlan::query()->firstOrFail();
        $plan->update([
            'features' => array_merge($plan->features ?? [], ['attendance' => true]),
        ]);
    }
}
