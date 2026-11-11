<?php

namespace App\Http\Controllers\Central;

use App\Domain\Crm\Models\DemoRequest;
use App\Domain\Crm\Services\DemoRequestService;
use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformAssignableStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
            'salesStaff' => PlatformAssignableStaff::optionsFor($request->user()),
            'canAssignToAdmins' => $request->user()->isPlatformSuperAdminRole(),
        ]);
    }

    public function show(Request $request, DemoRequest $demo): Response
    {
        $this->authorize('view', $demo);

        $demo->load(['assignee:id,name,email', 'lead', 'reviewer:id,name']);

        return Inertia::render('Platform/Demos/Show', [
            'demo' => $demo,
            'statuses' => DemoRequest::STATUSES,
            'salesStaff' => PlatformAssignableStaff::optionsFor($request->user()),
            'canAssignToAdmins' => $request->user()->isPlatformSuperAdminRole(),
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

        if (array_key_exists('assigned_to', $data)) {
            $data['assigned_to'] = PlatformAssignableStaff::normalize(
                $request->user(),
                $data['assigned_to'],
                $demo->assigned_to !== null ? (int) $demo->assigned_to : null,
            );
        }

        $this->demos->update($demo, $data, $request->user());

        return redirect()
            ->route('platform.demos.index')
            ->with('success', __('platform.demo_updated'));
    }

    public function destroy(DemoRequest $demo): RedirectResponse
    {
        $this->authorize('delete', $demo);

        $demo->delete();

        return redirect()
            ->route('platform.demos.index')
            ->with('success', __('platform.demo_deleted'));
    }

}
