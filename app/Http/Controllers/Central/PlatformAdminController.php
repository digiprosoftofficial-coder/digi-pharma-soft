<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformAdminController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user() instanceof User && auth()->user()->shouldUsePlatformDashboard(), 403);

        $admins = User::query()
            ->where('is_platform_super_admin', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'email_verified_at', 'created_at']);

        return Inertia::render('Platform/Admins/Index', [
            'admins' => $admins,
        ]);
    }
}
