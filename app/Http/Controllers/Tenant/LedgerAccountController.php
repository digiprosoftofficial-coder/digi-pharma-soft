<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Accounting\Models\LedgerEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class LedgerAccountController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', LedgerAccount::class);

        $accounts = LedgerAccount::query()->orderBy('code')->get();

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LedgerAccount::class);

        return Inertia::render('Accounts/AccountForm', [
            'account' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LedgerAccount::class);

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:32',
                Rule::unique('ledger_accounts', 'code')->where('tenant_id', tenant_id()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:32'],
        ]);

        LedgerAccount::query()->create($validated);

        return redirect()->route('tenant.accounts.index')->with('success', __('Account created.'));
    }

    public function show(LedgerAccount $ledgerAccount): Response
    {
        $this->authorize('view', $ledgerAccount);

        $entries = LedgerEntry::query()
            ->where('ledger_account_id', $ledgerAccount->getKey())
            ->orderByDesc('posted_at')
            ->paginate(30);

        return Inertia::render('Accounts/Show', [
            'account' => $ledgerAccount,
            'entries' => $entries,
        ]);
    }
}
