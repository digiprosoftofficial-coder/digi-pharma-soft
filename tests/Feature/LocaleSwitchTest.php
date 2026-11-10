<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_persists_in_session(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->post('/locale', ['locale' => 'bn'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'bn'));
    }

    public function test_marketing_home_uses_platform_default_locale(): void
    {
        $this->seed(DatabaseSeeder::class);

        PlatformSettings::update(['default_locale' => 'bn']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('locale', 'bn'));
    }
}
