<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class PackageSaleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', \App\Domain\Sales\Models\Sale::class);

        return Inertia::render('Sales/PackageSale');
    }
}
