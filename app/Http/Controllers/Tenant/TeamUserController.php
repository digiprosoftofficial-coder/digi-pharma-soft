<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenant\Models\Branch;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

    /**
     * @return list<array{id:int,name:string,code:string}>
     */
    private function branchOptions(): array
    {
        if (! TenantFeatures::multiBranchEnabled(tenant())) {
            return [];
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Branch $branch) => [
                'id' => (int) $branch->getKey(),
                'name' => $branch->name,
                'code' => $branch->code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{name:string,email:string,password:?string,role:string,assigned_branch_id:?int}
     */
    private function validatedUserPayload(Request $request, ?User $existing = null): array
    {
        $multiBranch = TenantFeatures::multiBranchEnabled(tenant());
        $roles = Role::query()->where('tenant_id', tenant_id())->pluck('name')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)
                    ->where(fn ($q) => $q->where('tenant_id', tenant_id()))
                    ->ignore($existing?->getKey()),
            ],
            'password' => [$existing ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($roles)],
            'assigned_branch_id' => [
                Rule::requiredIf(fn () => $multiBranch && $request->input('role') === 'manager'),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('tenant_id', tenant_id())->where('is_active', true)),
            ],
        ], [
            'assigned_branch_id.required' => __('branches.assigned_branch_required'),
        ]);

        if ($validated['role'] === 'pharmacy owner' || ! $multiBranch) {
            $validated['assigned_branch_id'] = null;
        } else {
            $validated['assigned_branch_id'] = isset($validated['assigned_branch_id'])
                ? (int) $validated['assigned_branch_id']
                : null;
        }

        return $validated;
    }

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->where('tenant_id', tenant_id())
            ->where('is_platform_super_admin', false)
            ->with(['roles', 'assignedBranch:id,name,code'])
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Team/Users/Index', [
            'users' => $users,
            'roles' => Role::query()
                ->where('tenant_id', tenant_id())
                ->orderBy('name')
                ->pluck('name'),
            'multiBranch' => TenantFeatures::multiBranchEnabled(tenant()),
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
            'branches' => $this->branchOptions(),
            'multiBranch' => TenantFeatures::multiBranchEnabled(tenant()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $this->validatedUserPayload($request);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'tenant_id' => tenant_id(),
            'assigned_branch_id' => $validated['assigned_branch_id'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) tenant_id());
        $user->syncRoles([$validated['role']]);

        return redirect()->route('tenant.team.users.index')->with('success', __('User created.'));
    }

    public function edit(int $id): Response
    {
        $user = $this->scopedUser($id);
        $this->authorize('update', $user);

        return Inertia::render('Team/Users/Form', [
            'user' => $user->load(['roles', 'assignedBranch:id,name,code']),
            'roles' => Role::query()
                ->where('tenant_id', tenant_id())
                ->orderBy('name')
                ->pluck('name'),
            'branches' => $this->branchOptions(),
            'multiBranch' => TenantFeatures::multiBranchEnabled(tenant()),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->scopedUser($id);
        $this->authorize('update', $user);

        $validated = $this->validatedUserPayload($request, $user);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->assigned_branch_id = $validated['assigned_branch_id'];
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) tenant_id());
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
