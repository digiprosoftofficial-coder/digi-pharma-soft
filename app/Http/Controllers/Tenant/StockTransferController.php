<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Inventory\Services\StockTransferService;
use App\Domain\Tenant\Models\Branch;
use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class StockTransferController extends Controller
{
    public function __construct(private readonly StockTransferService $transfers) {}

    public function index(): Response
    {
        $this->authorize('viewAny', StockTransfer::class);

        $transfers = StockTransfer::query()
            ->with(['fromBranch:id,name,code', 'toBranch:id,name,code'])
            ->withCount('lines')
            ->orderByDesc('transferred_at')
            ->paginate(20);

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $transfers,
            'multiBranch' => TenantFeatures::multiBranchEnabled(tenant()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', StockTransfer::class);

        $multiBranch = TenantFeatures::multiBranchEnabled(tenant());

        return Inertia::render('StockTransfers/Create', [
            'branches' => $multiBranch
                ? Branch::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'code', 'is_default'])
                : [],
            'multiBranch' => $multiBranch,
            'currentBranchId' => \branch_id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockTransfer::class);

        $tid = tenant_id();
        $multiBranch = TenantFeatures::multiBranchEnabled(tenant());

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'to_branch_id' => $multiBranch
                ? ['required', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $tid)]
                : ['nullable'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.from_batch_id' => ['required', 'integer', Rule::exists('product_batches', 'id')->where('tenant_id', $tid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $toBranchId = $multiBranch
            ? (int) $validated['to_branch_id']
            : \branch_id();

        $lines = array_map(
            fn (array $line) => [
                'from_batch_id' => (int) $line['from_batch_id'],
                'to_branch_id' => $toBranchId,
                'quantity' => (float) $line['quantity'],
            ],
            $validated['lines'],
        );

        $this->transfers->recordTransfer($lines, $validated['notes'] ?? null);

        return redirect()->route('tenant.stock-transfers.index')->with('success', __('Transfer completed.'));
    }
}
