<?php

namespace App\Http\Controllers\Marketing;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Services\DemoRequestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\StoreDemoRequest;
use App\Support\Platform\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class MarketingController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Marketing/Home', [
            'plans' => $this->publicPlans(),
            'currency' => PlatformSettings::defaultCurrency(),
            'selfServiceEnabled' => PlatformSettings::selfServiceEnabled(),
        ]);
    }

    public function pricing(): Response
    {
        return Inertia::render('Marketing/Pricing', [
            'plans' => $this->publicPlans(),
            'currency' => PlatformSettings::defaultCurrency(),
            'selfServiceEnabled' => PlatformSettings::selfServiceEnabled(),
        ]);
    }

    public function demoForm(): Response
    {
        return Inertia::render('Marketing/Demo', [
            'support' => [
                'email' => PlatformSettings::get()['support_email'],
                'phone' => PlatformSettings::get()['support_phone'],
            ],
        ]);
    }

    public function storeDemo(StoreDemoRequest $request, DemoRequestService $demos): RedirectResponse
    {
        $demos->submitPublic($request->validated());

        return redirect()
            ->route('marketing.demo')
            ->with('success', __('platform.demo_submitted'));
    }

    /**
     * @return list<SubscriptionPlan>
     */
    private function publicPlans(): array
    {
        return SubscriptionPlan::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }
}
