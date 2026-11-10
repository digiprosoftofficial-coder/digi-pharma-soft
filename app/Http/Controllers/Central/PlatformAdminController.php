<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

final class PlatformAdminController extends Controller
{
    private const ROLE_SUPER = 'super admin';

    private const ROLE_SALES = 'sales';

    public function index(Request $request): Response
    {
        $this->authorizeSuperAdmin($request);

        return Inertia::render('Platform/Admins/Index', [
            'admins' => $this->platformUsers(),
            'roles' => [
                self::ROLE_SUPER,
                self::ROLE_SALES,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([self::ROLE_SUPER, self::ROLE_SALES])],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'tenant_id' => null,
        ]);

        $user->forceFill([
            'is_platform_super_admin' => $data['role'] === self::ROLE_SUPER,
            'email_verified_at' => now(),
        ])->save();

        $this->withPlatformTeam(function () use ($user, $data): void {
            $user->syncRoles([$data['role']]);
        });

        return redirect()
            ->route('platform.admins.index')
            ->with('success', __('platform.admin_created'));
    }

    public function update(Request $request, User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $this->assertPlatformUser($admin);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->getKey())],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in([self::ROLE_SUPER, self::ROLE_SALES])],
        ]);

        $admin->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }

        $admin->forceFill([
            'is_platform_super_admin' => $data['role'] === self::ROLE_SUPER,
        ])->save();

        $this->withPlatformTeam(function () use ($admin, $data): void {
            $admin->syncRoles([$data['role']]);
        });

        return redirect()
            ->route('platform.admins.index')
            ->with('success', __('platform.admin_updated'));
    }

    public function destroy(Request $request, User $admin): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $this->assertPlatformUser($admin);

        if ((int) $admin->getKey() === (int) $request->user()->getKey()) {
            return back()->with('error', __('platform.admin_cannot_delete_self'));
        }

        $admin->delete();

        return redirect()
            ->route('platform.admins.index')
            ->with('success', __('platform.admin_deleted'));
    }

    /**
     * @return list<array{id:int, name:string, email:string, role:string, is_platform_super_admin:bool, email_verified_at:?string, created_at:?string}>
     */
    private function platformUsers(): array
    {
        return $this->withPlatformTeam(function () {
            return User::query()
                ->whereNull('tenant_id')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'is_platform_super_admin', 'email_verified_at', 'created_at'])
                ->map(function (User $user) {
                    $role = $user->hasRole(self::ROLE_SUPER)
                        ? self::ROLE_SUPER
                        : ($user->hasRole(self::ROLE_SALES) ? self::ROLE_SALES : '—');

                    return [
                        'id' => $user->getKey(),
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $role,
                        'is_platform_super_admin' => (bool) $user->is_platform_super_admin,
                        'email_verified_at' => optional($user->email_verified_at)?->toIso8601String(),
                        'created_at' => optional($user->created_at)?->toIso8601String(),
                    ];
                })
                ->values()
                ->all();
        });
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $allowed = (bool) $user->is_platform_super_admin
            || $this->withPlatformTeam(fn () => $user->hasRole(self::ROLE_SUPER));

        abort_unless($allowed, 403);
    }

    private function assertPlatformUser(User $admin): void
    {
        abort_unless($admin->tenant_id === null, 404);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withPlatformTeam(callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
