<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Supplier;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class SupplierBillsController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        $suppliers = Supplier::query()
            ->withSum('purchases as purchases_sum_due', 'due')
            ->orderByDesc('purchases_sum_due')
            ->paginate(20);

        return Inertia::render('Purchases/SupplierBills', [
            'suppliers' => $suppliers,
        ]);
    }
}
