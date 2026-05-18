<?php

namespace App\Http\Controllers\Central;

use App\Domain\Platform\Models\PlatformAnnouncement;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformAnnouncementController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PlatformAnnouncement::class);

        $announcements = PlatformAnnouncement::query()
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (PlatformAnnouncement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'severity' => $a->severity,
                'starts_at' => $a->starts_at?->toIso8601String(),
                'ends_at' => $a->ends_at?->toIso8601String(),
                'is_active' => $a->is_active,
                'is_live' => $a->isCurrentlyActive(),
            ]);

        return Inertia::render('Platform/Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PlatformAnnouncement::class);

        return Inertia::render('Platform/Announcements/Form', ['announcement' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PlatformAnnouncement::class);

        PlatformAnnouncement::query()->create($this->validated($request));

        return redirect()->route('platform.announcements.index')->with('success', __('platform.announcement_created'));
    }

    public function edit(PlatformAnnouncement $announcement): Response
    {
        $this->authorize('update', $announcement);

        return Inertia::render('Platform/Announcements/Form', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'severity' => $announcement->severity,
                'starts_at' => $announcement->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $announcement->ends_at?->format('Y-m-d\TH:i'),
                'is_active' => $announcement->is_active,
            ],
        ]);
    }

    public function update(Request $request, PlatformAnnouncement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update($this->validated($request));

        return redirect()->route('platform.announcements.index')->with('success', __('platform.announcement_updated'));
    }

    public function destroy(PlatformAnnouncement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return redirect()->route('platform.announcements.index')->with('success', __('platform.announcement_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'severity' => ['required', Rule::in(['info', 'warning', 'danger'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
