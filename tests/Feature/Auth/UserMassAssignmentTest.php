<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class UserMassAssignmentTest extends TestCase
{
    public function test_is_platform_super_admin_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();

        $user->fill(['is_platform_super_admin' => true])->save();

        $this->assertFalse($user->fresh()->is_platform_super_admin);
    }
}
