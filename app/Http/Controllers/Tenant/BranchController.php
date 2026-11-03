<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Services\BranchService;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use App\Support\Tenant\TenantLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branches) {}

    public function index(): Response
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403, __('branches.feature_disabled'));
        $this->authorize('viewAny', Branch::class);

        $tenant = tenant();
        $max = TenantLimits::maxBranches($tenant);

        return Inertia::render('Branches/Index', [
            'branches' => Branch::query()->orderByDesc('is_default')->orderBy('name')->paginate(20),
            'branchCount' => Branch::query()->count(),
            'maxBranches' => $max,
            'canManage' => auth()->user()?->can('branches.manage') ?? false,
            'multiBranchEnabled' => TenantFeatures::multiBranchEnabled($tenant),
        ]);
    }

    public function create(): Response
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403, __('branches.feature_disabled'));
        $this->authorize('create', Branch::class);

        return Inertia::render('Branches/Form', ['branch' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403, __('branches.feature_disabled'));
        $this->authorize('create', Branch::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('branches', 'code')->where('tenant_id', tenant_id())],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_active' => ['boolean'],
        ]);

        try {
            $this->branches->create($validated);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('tenant.branches.index')->with('success', __('branches.created'));
    }

    public function edit(Branch $branch): Response
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403, __('branches.feature_disabled'));
        $this->authorize('update', $branch);

        return Inertia::render('Branches/Form', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403, __('branches.feature_disabled'));
        $this->authorize('update', $branch);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('branches', 'code')->where('tenant_id', tenant_id())->ignore($branch->getKey())],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_active' => ['boolean'],
        ]);

        try {
            $this->branches->update($branch, $validated);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('tenant.branches.index')->with('success', __('branches.updated'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        abort_unless(TenantFeatures::multiBranchEnabled(tenant()), 403, __('branches.feature_disabled'));
        $this->authorize('delete', $branch);

        try {
            $this->branches->delete($branch);
        } catch (RuntimeException $e) {
            return back()->withErrors(['branch' => $e->getMessage()]);
        }

        return redirect()->route('tenant.branches.index')->with('success', __('branches.deleted'));
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', tenant_id())],
        ]);

        $branch = Branch::query()->whereKey($validated['branch_id'])->where('is_active', true)->firstOrFail();
        $user = $request->user();

        if ($user?->isLockedToAssignedBranch()
            && (int) $branch->getKey() !== (int) $user->assignedBranchId()
        ) {
            return back()->withErrors(['branch_id' => __('branches.switch_locked')])
                ->with('app_dialog', [
                    'type' => 'forbidden',
                    'title' => __('errors.forbidden_title'),
                    'message' => __('branches.switch_locked'),
                    'action_label' => __('errors.got_it'),
                    'action_href' => null,
                    'action_method' => null,
                ]);
        }

        $request->session()->put('active_branch_id', $branch->getKey());

        return $this->redirectAfterBranchSwitch($request)
            ->with('success', __('branches.switched', ['name' => $branch->name]));
    }

    /**
     * Dashboard may carry ?branch_id=all (or a locked branch). After switching the
     * active branch, drop that lock so the executive dashboard follows the new branch.
     */
    private function redirectAfterBranchSwitch(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('Referer');
        if (! is_string($referer) || $referer === '') {
            return back();
        }

        $parts = parse_url($referer);
        $path = $parts['path'] ?? '';
        if (! is_string($path) || ! str_ends_with(rtrim($path, '/'), '/dashboard')) {
            return back();
        }

        parse_str($parts['query'] ?? '', $query);
        unset($query['branch_id']);

        $target = $path;
        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        return redirect()->to($target);
    }
}
