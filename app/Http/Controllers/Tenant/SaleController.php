<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class SaleController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $sales = Sale::query()
            ->with([
                'customer',
                'lines.product',
                'lines.batch',
            ])
            ->orderByDesc('sold_at')
            ->paginate(20);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
        ]);
    }
}
