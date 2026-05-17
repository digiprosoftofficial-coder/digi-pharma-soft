<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

final class PlatformAuditController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user() instanceof User && auth()->user()->shouldUsePlatformDashboard(), 403);

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->where(function ($q) {
                $q->whereNull('tenant_id')
                    ->orWhereIn('event', [
                        'tenant.provisioned', 'tenant.suspended', 'tenant.unsuspended',
                    ]);
            })
            ->orderByDesc('created_at')
            ->paginate(30);

        return Inertia::render('Platform/Audit/Index', [
            'activities' => $activities->through(fn (Activity $a) => [
                'id' => $a->getKey(),
                'description' => $a->description,
                'event' => $a->event,
                'created_at' => $a->created_at?->toIso8601String(),
                'causer_name' => $a->causer?->name,
                'tenant_id' => $a->tenant_id,
                'properties' => $a->properties?->toArray() ?? [],
            ]),
        ]);
    }
}
