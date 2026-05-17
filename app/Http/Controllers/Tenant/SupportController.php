<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformSettings;
use Inertia\Inertia;
use Inertia\Response;

final class SupportController extends Controller
{
    public function index(): Response
    {
        $settings = PlatformSettings::get();

        return Inertia::render('Support/Index', [
            'support' => [
                'email' => $settings['support_email'],
                'phone' => $settings['support_phone'],
            ],
        ]);
    }
}
