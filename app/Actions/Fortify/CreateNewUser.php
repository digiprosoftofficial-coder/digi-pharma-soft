<?php

namespace App\Actions\Fortify;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'tenant_slug' => ['required', 'string', 'max:64', Rule::exists('tenants', 'slug')->where('is_active', true)],
        ])->validate();

        $tenant = Tenant::query()->where('slug', $input['tenant_slug'])->where('is_active', true)->firstOrFail();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->where(fn ($q) => $q->where('tenant_id', $tenant->getKey())),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input, $tenant) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'tenant_id' => $tenant->getKey(),
                'is_platform_super_admin' => false,
            ]);

            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
            $user->assignRole('cashier');

            return $user;
        });
    }
}
