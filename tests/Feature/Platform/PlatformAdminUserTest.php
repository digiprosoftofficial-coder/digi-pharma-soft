<?php

namespace Tests\Feature\Platform;

use App\Domain\Crm\Models\DemoRequest;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_sales_user(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->post('/platform/admins', [
            'name' => 'Sales Person',
            'email' => 'sales@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ])->assertRedirect(route('platform.admins.index'));

        $sales = User::query()->where('email', 'sales@example.com')->firstOrFail();
        $this->assertNull($sales->tenant_id);
        $this->assertFalse((bool) $sales->is_platform_super_admin);

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);
        try {
            $this->assertTrue($sales->hasRole('sales'));
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }

        $this->actingAs($admin)
            ->get('/platform/admins')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Admins/Index')
                ->has('admins')
                ->where('admins', fn ($admins) => collect($admins)->contains(
                    fn ($row) => ($row['email'] ?? null) === 'sales@example.com' && ($row['role'] ?? null) === 'sales'
                )));
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('platform.admins.index'))
            ->delete("/platform/admins/{$admin->getKey()}")
            ->assertRedirect(route('platform.admins.index'));

        $this->assertDatabaseHas('users', ['id' => $admin->getKey()]);
    }

    public function test_sales_can_assign_demo_to_other_sales_but_not_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->post('/platform/admins', [
            'name' => 'Sales One',
            'email' => 'sales1@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ])->assertRedirect(route('platform.admins.index'));

        $this->actingAs($admin)->post('/platform/admins', [
            'name' => 'Sales Two',
            'email' => 'sales2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ])->assertRedirect(route('platform.admins.index'));

        $sales1 = User::query()->where('email', 'sales1@example.com')->firstOrFail();
        $sales2 = User::query()->where('email', 'sales2@example.com')->firstOrFail();

        $demo = DemoRequest::query()->create([
            'pharmacy_name' => 'Assign Pharmacy',
            'owner_name' => 'Owner',
            'mobile' => '01900000077',
            'branch_count' => 1,
            'status' => DemoRequest::STATUS_PENDING,
            'assigned_to' => $sales1->getKey(),
        ]);

        $this->actingAs($sales1)
            ->put("/platform/demos/{$demo->getKey()}", [
                'status' => DemoRequest::STATUS_SCHEDULED,
                'assigned_to' => $sales2->getKey(),
                'meeting_notes' => 'handoff to sales two',
            ])
            ->assertRedirect();

        $demo->refresh();
        $this->assertSame($sales2->getKey(), (int) $demo->assigned_to);
        $this->assertSame(DemoRequest::STATUS_SCHEDULED, $demo->status);

        $this->actingAs($sales1)
            ->from(route('platform.demos.show', $demo))
            ->put("/platform/demos/{$demo->getKey()}", [
                'status' => DemoRequest::STATUS_SCHEDULED,
                'assigned_to' => $admin->getKey(),
                'meeting_notes' => 'try assign admin',
            ])
            ->assertRedirect(route('platform.demos.show', $demo))
            ->assertSessionHasErrors('assigned_to');

        $demo->refresh();
        $this->assertSame($sales2->getKey(), (int) $demo->assigned_to);
    }

    public function test_super_admin_can_assign_demo_to_sales(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->post('/platform/admins', [
            'name' => 'Sales Person',
            'email' => 'sales@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ])->assertRedirect(route('platform.admins.index'));

        $sales = User::query()->where('email', 'sales@example.com')->firstOrFail();

        $demo = DemoRequest::query()->create([
            'pharmacy_name' => 'Admin Assign Pharmacy',
            'owner_name' => 'Owner',
            'mobile' => '01900000088',
            'branch_count' => 1,
            'status' => DemoRequest::STATUS_PENDING,
            'assigned_to' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->put("/platform/demos/{$demo->getKey()}", [
                'status' => DemoRequest::STATUS_SCHEDULED,
                'assigned_to' => $sales->getKey(),
            ])
            ->assertRedirect();

        $demo->refresh();
        $this->assertSame($sales->getKey(), (int) $demo->assigned_to);
    }
}
