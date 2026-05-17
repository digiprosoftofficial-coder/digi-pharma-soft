<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Accounting\Models\LedgerEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LedgerEntryController extends Controller
{
    public function store(Request $request, LedgerAccount $ledgerAccount): RedirectResponse
    {
        $this->authorize('view', $ledgerAccount);
        abort_unless(auth()->user()?->can('accounting.manage'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'direction' => ['required', 'in:debit,credit'],
            'memo' => ['nullable', 'string', 'max:2000'],
        ]);

        LedgerEntry::query()->create([
            'ledger_account_id' => $ledgerAccount->getKey(),
            'amount' => $validated['amount'],
            'direction' => $validated['direction'],
            'memo' => $validated['memo'] ?? null,
            'posted_at' => now(),
        ]);

        return redirect()->route('tenant.accounts.show', $ledgerAccount)->with('success', __('Entry posted.'));
    }
}
