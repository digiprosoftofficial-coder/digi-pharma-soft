<?php

namespace App\Http\Controllers\Central;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\MasterProductSuggestion;
use App\Domain\Catalog\Services\MasterProductSuggestionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformMasterSuggestionController extends Controller
{
    public function __construct(
        private readonly MasterProductSuggestionService $suggestions,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MasterProductSuggestion::class);

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,merged,all'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $validated['status'] ?? 'pending';
        $q = trim((string) ($validated['q'] ?? ''));

        $query = MasterProductSuggestion::query()
            ->with(['tenant:id,name,slug', 'suggestedBy:id,name,email', 'product:id,name,sku'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('generic_name', 'like', '%'.$q.'%')
                    ->orWhere('barcode', $q)
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', '%'.$q.'%'));
            });
        }

        $rows = $query->paginate(20)->withQueryString()->through(function (MasterProductSuggestion $s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'generic_name' => $s->generic_name,
                'strength' => $s->strength,
                'manufacturer_name' => $s->manufacturer_name,
                'product_type' => $s->product_type,
                'sku' => $s->sku,
                'barcode' => $s->barcode,
                'mrp' => (string) $s->mrp,
                'default_purchase_price' => (string) $s->default_purchase_price,
                'status' => $s->status,
                'created_at' => $s->created_at?->toDateTimeString(),
                'reviewed_at' => $s->reviewed_at?->toDateTimeString(),
                'review_note' => $s->review_note,
                'pharmacy' => $s->tenant ? [
                    'id' => $s->tenant->id,
                    'name' => $s->tenant->name,
                    'slug' => $s->tenant->slug,
                ] : null,
                'suggested_by' => $s->suggestedBy?->name,
                'candidates' => $s->isPending() ? $this->suggestions->matchCandidates($s) : [],
            ];
        });

        return Inertia::render('Platform/MasterCatalog/Suggestions', [
            'suggestions' => $rows,
            'filters' => [
                'status' => $status,
                'q' => $q,
            ],
            'stats' => [
                'pending' => MasterProductSuggestion::query()->where('status', MasterProductSuggestion::STATUS_PENDING)->count(),
                'approved' => MasterProductSuggestion::query()->where('status', MasterProductSuggestion::STATUS_APPROVED)->count(),
                'rejected' => MasterProductSuggestion::query()->where('status', MasterProductSuggestion::STATUS_REJECTED)->count(),
                'merged' => MasterProductSuggestion::query()->where('status', MasterProductSuggestion::STATUS_MERGED)->count(),
            ],
        ]);
    }

    public function approve(Request $request, MasterProductSuggestion $suggestion): RedirectResponse
    {
        $this->authorize('update', $suggestion);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->suggestions->approve($suggestion, $request->user(), $validated['review_note'] ?? null);

        return back()->with('success', __('platform.suggestion_approved'));
    }

    public function merge(Request $request, MasterProductSuggestion $suggestion): RedirectResponse
    {
        $this->authorize('update', $suggestion);

        $validated = $request->validate([
            'master_product_id' => ['required', 'integer', 'exists:master_products,id'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $master = MasterProduct::query()->findOrFail($validated['master_product_id']);
        $this->suggestions->merge($suggestion, $master, $request->user(), $validated['review_note'] ?? null);

        return back()->with('success', __('platform.suggestion_merged'));
    }

    public function reject(Request $request, MasterProductSuggestion $suggestion): RedirectResponse
    {
        $this->authorize('update', $suggestion);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->suggestions->reject($suggestion, $request->user(), $validated['review_note'] ?? null);

        return back()->with('success', __('platform.suggestion_rejected'));
    }
}
