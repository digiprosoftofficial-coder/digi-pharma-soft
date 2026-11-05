<?php

namespace App\Http\Controllers\Central;

use App\Domain\Crm\Models\DemoRequest;
use App\Domain\Crm\Services\DemoRequestService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

final class PlatformDemoController extends Controller
{
    public function __construct(private readonly DemoRequestService $demos) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DemoRequest::class);

        return Inertia::render('Platform/Demos/Index', [
            'demos' => $this->demos->paginate($request->only(['search', 'status'])),
            'filters' => $request->only(['search', 'status']),
            'stats' => $this->demos->dashboardStats(),
            'statuses' => DemoRequest::STATUSES,
            'salesStaff' => $this->salesStaff(),
        ]);
    }

    public function show(DemoRequest $demo): Response
    {
        $this->authorize('view', $demo);

        $demo->load(['assignee:id,name,email', 'lead', 'reviewer:id,name']);

        return Inertia::render('Platform/Demos/Show', [
            'demo' => $demo,
            'statuses' => DemoRequest::STATUSES,
            'salesStaff' => $this->salesStaff(),
        ]);
    }

    public function update(Request $request, DemoRequest $demo): RedirectResponse
    {
        $this->authorize('update', $demo);

        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(DemoRequest::STATUSES)],
            'scheduled_at' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'meeting_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->demos->update($demo, $data, $request->user());

        return back()->with('success', __('platform.demo_updated'));
    }

    /**
     * @return list<\App\Models\User>
     */
    private function salesStaff(): array
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);

        try {
            return User::query()
                ->whereNull('tenant_id')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->all();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }
    }
}
