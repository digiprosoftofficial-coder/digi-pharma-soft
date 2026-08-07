<?php

namespace Tests\Feature\Tenant;

use App\Domain\Tenant\Models\PharmacyNote;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantFeatures;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyNoteTest extends TestCase
{
    use RefreshDatabase;

    private function enableNotes(Tenant $tenant): void
    {
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);
        $features = $plan->features ?? [];
        $features['pharmacy_notes'] = true;
        $plan->features = $features;
        $plan->save();
        $tenant->refresh();
    }

    public function test_owner_can_crud_pin_and_complete_note_when_feature_on(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $this->enableNotes($tenant);

        $this->actingAs($user)
            ->get('/notes')
            ->assertOk();

        $this->actingAs($user)
            ->post('/notes', [
                'title' => 'Buy list',
                'body' => 'Need Napa Extra',
                'type' => 'buy',
            ])
            ->assertRedirect();

        $note = PharmacyNote::query()->where('body', 'Need Napa Extra')->firstOrFail();
        $this->assertSame($user->getKey(), $note->user_id);
        $this->assertFalse($note->is_pinned);
        $this->assertFalse($note->is_done);

        $this->actingAs($user)
            ->patch("/notes/{$note->getKey()}/pin")
            ->assertRedirect();
        $note->refresh();
        $this->assertTrue($note->is_pinned);

        $this->actingAs($user)
            ->patch("/notes/{$note->getKey()}/done")
            ->assertRedirect();
        $note->refresh();
        $this->assertTrue($note->is_done);
        $this->assertFalse($note->is_pinned);
        $this->assertNotNull($note->done_at);

        $this->actingAs($user)
            ->put("/notes/{$note->getKey()}", [
                'title' => 'Updated',
                'body' => 'Need Napa Extra x2',
                'type' => 'reminder',
            ])
            ->assertRedirect();
        $note->refresh();
        $this->assertSame('Updated', $note->title);
        $this->assertSame('reminder', $note->type);

        $this->actingAs($user)
            ->delete("/notes/{$note->getKey()}")
            ->assertRedirect();

        $this->assertDatabaseMissing('pharmacy_notes', ['id' => $note->getKey()]);
    }

    public function test_notes_forbidden_when_plan_feature_off(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();

        $this->assertFalse(TenantFeatures::pharmacyNotesEnabled($tenant));

        $this->actingAs($user)->get('/notes')->assertForbidden();
        $this->actingAs($user)->post('/notes', [
            'body' => 'Should fail',
            'type' => 'general',
        ])->assertForbidden();
    }

    public function test_cashier_cannot_access_notes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $this->enableNotes($tenant);

        $cashier = User::query()->create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Cashier',
            'email' => 'cashier-notes@example.com',
            'password' => bcrypt('password'),
        ]);
        $cashier->forceFill(['email_verified_at' => now()])->save();
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)->get('/notes')->assertForbidden();
        $this->actingAs($cashier)->post('/notes', [
            'body' => 'Nope',
            'type' => 'general',
        ])->assertForbidden();
    }

    public function test_tenant_isolation_hides_other_tenant_notes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $this->enableNotes($tenant);

        $other = Tenant::query()->create([
            'name' => 'Other Pharmacy',
            'slug' => 'other-pharmacy-notes',
            'is_active' => true,
        ]);

        PharmacyNote::query()->withoutGlobalScopes()->create([
            'tenant_id' => $other->getKey(),
            'user_id' => $user->getKey(),
            'title' => null,
            'body' => 'Secret other tenant note',
            'type' => 'general',
            'is_pinned' => false,
            'is_done' => false,
        ]);

        app(TenantContext::class)->set($tenant);

        PharmacyNote::query()->create([
            'user_id' => $user->getKey(),
            'body' => 'My pharmacy note',
            'type' => 'buy',
        ]);

        $this->actingAs($user)
            ->get('/notes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notes/Index')
                ->has('notes', 1)
                ->where('notes.0.body', 'My pharmacy note'));
    }
}
