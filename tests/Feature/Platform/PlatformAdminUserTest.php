<?php

namespace Tests\Feature\Platform;

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

        $this->actingAs($admin)
            ->post('/platform/admins', [
                'name' => 'Sales Person',
                'email' => 'sales@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'sales',
            ])
            ->assertRedirect(route('platform.admins.index'));

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

    public function test_sales_cannot_change_demo_assignment(): void
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

        $demo = \App\Domain\Crm\Models\DemoRequest::query()->create([
            'pharmacy_name' => 'Assign Lock Pharmacy',
            'owner_name' => 'Owner',
            'mobile' => '01900000077',
            'branch_count' => 1,
            'status' => \App\Domain\Crm\Models\DemoRequest::STATUS_PENDING,
            'assigned_to' => $admin->getKey(),
        ]);

        $this->actingAs($sales)
            ->put("/platform/demos/{$demo->getKey()}", [
                'status' => \App\Domain\Crm\Models\DemoRequest::STATUS_SCHEDULED,
                'assigned_to' => $sales->getKey(),
                'meeting_notes' => 'try reassign',
            ])
            ->assertRedirect();

        $demo->refresh();
        $this->assertSame($admin->getKey(), (int) $demo->assigned_to);
        $this->assertSame(\App\Domain\Crm\Models\DemoRequest::STATUS_SCHEDULED, $demo->status);
    }
}
