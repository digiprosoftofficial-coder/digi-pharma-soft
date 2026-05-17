<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class SmsController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('sms.send'), 403);

        return Inertia::render('Sms/Index');
    }
}
