<?php

namespace Tests\Feature\Hr;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Hr\Models\Employee;
use App\Domain\Hr\Models\LeaveType;
use App\Domain\Hr\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_is_gated_by_plan_feature(): void
    {
        $this->seed();
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get('/hr/payroll')
            ->assertForbidden();
    }

    public function test_owner_can_generate_and_finalize_payroll(): void
    {
        $this->seed();
        $this->enableHrPayroll();

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        Employee::query()->create([
            'tenant_id' => $owner->tenant_id,
            'employee_code' => 'PAY-1',
            'name' => 'Payroll Staff',
            'salary' => 20000,
            'is_active' => true,
        ]);

        $period = now()->format('Y-m');

        $this->actingAs($owner)
            ->post('/hr/payroll', ['period' => $period])
            ->assertRedirect();

        $run = PayrollRun::query()->where('period', $period)->firstOrFail();
        $this->assertSame('draft', $run->status);
        $this->assertSame(1, $run->lines()->count());

        $this->actingAs($owner)
            ->post("/hr/payroll/{$run->getKey()}/finalize")
            ->assertRedirect();

        $run->refresh();
        $this->assertSame('finalized', $run->status);
        $this->assertNotNull($run->finalized_at);
    }

    public function test_can_create_leave_type_and_request(): void
    {
        $this->seed();
        $this->enableHrPayroll();

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $employee = Employee::query()->create([
            'tenant_id' => $owner->tenant_id,
            'employee_code' => 'LV-1',
            'name' => 'Leave Staff',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post('/hr/leave-types', [
                'name' => 'Annual',
                'days_per_year' => 10,
                'is_paid' => true,
            ])
            ->assertRedirect();

        $leaveType = LeaveType::query()->where('name', 'Annual')->firstOrFail();

        $this->actingAs($owner)
            ->post('/hr/leave-requests', [
                'employee_id' => $employee->getKey(),
                'leave_type_id' => $leaveType->getKey(),
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->getKey(),
            'leave_type_id' => $leaveType->getKey(),
            'status' => 'pending',
        ]);
    }

    private function enableHrPayroll(): void
    {
        $plan = SubscriptionPlan::query()->firstOrFail();
        $plan->update([
            'features' => array_merge($plan->features ?? [], ['hr_payroll' => true]),
        ]);
    }
}
