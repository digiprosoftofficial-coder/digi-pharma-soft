<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class TeamUserController extends Controller
{
    private function scopedUser(int $id): User
    {
        return User::query()
            ->where('tenant_id', tenant_id())
            ->where('is_platform_super_admin', false)
            ->whereKey($id)
            ->firstOrFail();
    }

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->where('tenant_id', tenant_id())
            ->where('is_platform_super_admin', false)
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Team/Users/Index', [
            'users' => $users,
            'roles' => Role::query()
                ->where('tenant_id', tenant_id())
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Team/Users/Form', [
            'user' => null,
            'roles' => Role::query()
                ->where('tenant_id', tenant_id())
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->where(fn ($q) => $q->where('tenant_id', tenant_id())),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(
                Role::query()->where('tenant_id', tenant_id())->pluck('name')->all(),
            )],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => tenant_id(),
            'is_platform_super_admin' => false,
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId((int) tenant_id());
        $user->syncRoles([$validated['role']]);

        return redirect()->route('tenant.team.users.index')->with('success', __('User created.'));
    }

    public function edit(int $id): Response
    {
        $user = $this->scopedUser($id);
        $this->authorize('update', $user);

        return Inertia::render('Team/Users/Form', [
            'user' => $user->load('roles'),
            'roles' => Role::query()
                ->where('tenant_id', tenant_id())
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->scopedUser($id);
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->where(fn ($q) => $q->where('tenant_id', tenant_id()))->ignore($user->getKey()),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(
                Role::query()->where('tenant_id', tenant_id())->pluck('name')->all(),
            )],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId((int) tenant_id());
        $user->syncRoles([$validated['role']]);

        return redirect()->route('tenant.team.users.index')->with('success', __('User updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = $this->scopedUser($id);
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('tenant.team.users.index')->with('success', __('User removed.'));
    }
}
