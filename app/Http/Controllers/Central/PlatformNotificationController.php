<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->shouldUsePlatformDashboard(), 403);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30);

        return Inertia::render('Platform/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user()?->shouldUsePlatformDashboard(), 403);

        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->shouldUsePlatformDashboard(), 403);

        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', __('platform.notifications_marked_read'));
    }
}
