<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Models\PlatformPayment;
use App\Domain\Billing\Services\PlatformPaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformPaymentController extends Controller
{
    public function __construct(private readonly PlatformPaymentService $payments) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PlatformPayment::class);

        return Inertia::render('Platform/Payments/Index', [
            'payments' => $this->payments->paginate($request->only(['search', 'status', 'method'])),
            'filters' => $request->only(['search', 'status', 'method']),
            'stats' => $this->payments->dashboardStats(),
            'methods' => PlatformPayment::METHODS,
        ]);
    }

    public function verify(Request $request, PlatformPayment $payment): RedirectResponse
    {
        $this->authorize('manage', $payment);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->payments->verify($payment, $request->user(), $data['notes'] ?? null);

        return back()->with('success', __('platform.payment_verified'));
    }

    public function reject(Request $request, PlatformPayment $payment): RedirectResponse
    {
        $this->authorize('manage', $payment);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->payments->reject($payment, $request->user(), $data['notes'] ?? null);

        return back()->with('success', __('platform.payment_rejected'));
    }
}
