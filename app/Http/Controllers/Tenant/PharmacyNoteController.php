<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenant\Models\PharmacyNote;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePharmacyNoteRequest;
use App\Http\Requests\Tenant\UpdatePharmacyNoteRequest;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PharmacyNoteController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PharmacyNote::class, 'pharmacy_note');
    }

    public function index(Request $request): Response
    {
        $this->ensureFeatureEnabled();

        $tab = $request->string('tab', 'open')->toString();
        if (! in_array($tab, ['open', 'pinned', 'done', 'all'], true)) {
            $tab = 'open';
        }

        $type = $request->string('type')->toString();
        if ($type !== '' && ! in_array($type, PharmacyNote::TYPES, true)) {
            $type = '';
        }

        $q = trim($request->string('q')->toString());

        $notes = PharmacyNote::query()
            ->with('user:id,name')
            ->when($tab === 'open', fn ($query) => $query->where('is_done', false))
            ->when($tab === 'pinned', fn ($query) => $query->where('is_pinned', true)->where('is_done', false))
            ->when($tab === 'done', fn ($query) => $query->where('is_done', true))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('body', 'like', '%'.$q.'%')
                        ->orWhere('title', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get()
            ->map(fn (PharmacyNote $note) => $this->serialize($note));

        $counts = [
            'open' => PharmacyNote::query()->where('is_done', false)->count(),
            'pinned' => PharmacyNote::query()->where('is_pinned', true)->where('is_done', false)->count(),
            'done' => PharmacyNote::query()->where('is_done', true)->count(),
            'all' => PharmacyNote::query()->count(),
            'today' => PharmacyNote::query()->whereDate('created_at', now()->toDateString())->count(),
            'by_type' => [
                'buy' => PharmacyNote::query()->where('type', 'buy')->where('is_done', false)->count(),
                'contact' => PharmacyNote::query()->where('type', 'contact')->where('is_done', false)->count(),
                'reminder' => PharmacyNote::query()->where('type', 'reminder')->where('is_done', false)->count(),
                'general' => PharmacyNote::query()->where('type', 'general')->where('is_done', false)->count(),
            ],
        ];

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'filters' => [
                'tab' => $tab,
                'type' => $type !== '' ? $type : null,
                'q' => $q !== '' ? $q : null,
            ],
            'types' => PharmacyNote::TYPES,
            'counts' => $counts,
        ]);
    }

    public function store(StorePharmacyNoteRequest $request): RedirectResponse
    {
        $this->ensureFeatureEnabled();

        $data = $request->validated();

        PharmacyNote::query()->create([
            'user_id' => $request->user()->getKey(),
            'title' => filled($data['title'] ?? null) ? trim((string) $data['title']) : null,
            'body' => trim((string) $data['body']),
            'type' => $data['type'],
            'is_pinned' => false,
            'is_done' => false,
            'done_at' => null,
        ]);

        return redirect()
            ->route('tenant.notes.index', $this->preservedFilters($request))
            ->with('success', __('notes.created'));
    }

    public function update(UpdatePharmacyNoteRequest $request, PharmacyNote $pharmacyNote): RedirectResponse
    {
        $this->ensureFeatureEnabled();

        $data = $request->validated();

        $pharmacyNote->update([
            'title' => filled($data['title'] ?? null) ? trim((string) $data['title']) : null,
            'body' => trim((string) $data['body']),
            'type' => $data['type'],
        ]);

        return redirect()
            ->route('tenant.notes.index', $this->preservedFilters($request))
            ->with('success', __('notes.updated'));
    }

    public function destroy(Request $request, PharmacyNote $pharmacyNote): RedirectResponse
    {
        $this->ensureFeatureEnabled();
        $this->authorize('delete', $pharmacyNote);

        $pharmacyNote->delete();

        return redirect()
            ->route('tenant.notes.index', $this->filterQuery($request))
            ->with('success', __('notes.deleted'));
    }

    public function togglePin(Request $request, PharmacyNote $pharmacyNote): RedirectResponse
    {
        $this->ensureFeatureEnabled();
        $this->authorize('update', $pharmacyNote);

        $pharmacyNote->update([
            'is_pinned' => ! $pharmacyNote->is_pinned,
        ]);

        return redirect()
            ->route('tenant.notes.index', $this->filterQuery($request))
            ->with('success', $pharmacyNote->is_pinned ? __('notes.pinned') : __('notes.unpinned'));
    }

    public function toggleDone(Request $request, PharmacyNote $pharmacyNote): RedirectResponse
    {
        $this->ensureFeatureEnabled();
        $this->authorize('update', $pharmacyNote);

        $done = ! $pharmacyNote->is_done;

        $pharmacyNote->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'is_pinned' => $done ? false : $pharmacyNote->is_pinned,
        ]);

        return redirect()
            ->route('tenant.notes.index', $this->filterQuery($request))
            ->with('success', $done ? __('notes.marked_done') : __('notes.restored'));
    }

    private function ensureFeatureEnabled(): void
    {
        abort_unless(TenantFeatures::pharmacyNotesEnabled(tenant()), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(PharmacyNote $note): array
    {
        return [
            'id' => $note->id,
            'title' => $note->title,
            'body' => $note->body,
            'type' => $note->type,
            'is_pinned' => (bool) $note->is_pinned,
            'is_done' => (bool) $note->is_done,
            'done_at' => $note->done_at?->toIso8601String(),
            'created_at' => $note->created_at?->toIso8601String(),
            'updated_at' => $note->updated_at?->toIso8601String(),
            'user' => $note->user ? [
                'id' => $note->user->id,
                'name' => $note->user->name,
            ] : null,
        ];
    }

    /**
     * Filters for pin/done/delete (query or body; `type` is the filter, not note type).
     *
     * @return array<string, string>
     */
    private function filterQuery(Request $request): array
    {
        return array_filter([
            'tab' => $request->string('tab')->toString() ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'q' => $request->string('q')->toString() ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Filters after store/update — use filter_* so note `type` is not treated as a filter.
     *
     * @return array<string, string>
     */
    private function preservedFilters(Request $request): array
    {
        $tab = $request->input('filter_tab') ?? $request->input('tab', 'open');
        $type = $request->input('filter_type');
        $q = $request->input('filter_q') ?? $request->input('q');

        return array_filter([
            'tab' => filled($tab) ? (string) $tab : null,
            'type' => filled($type) ? (string) $type : null,
            'q' => filled($q) ? (string) $q : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
