<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformSystemHealth;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformHealthController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Health/Index', [
            'health' => PlatformSystemHealth::snapshot(includeFailedJobDetails: true),
        ]);
    }
}
