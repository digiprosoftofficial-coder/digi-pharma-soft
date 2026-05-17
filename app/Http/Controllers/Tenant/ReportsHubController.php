<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class ReportsHubController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        return Inertia::render('Reports/Hub');
    }
}
