<?php

namespace App\Http\Controllers\Central;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformPlanController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SubscriptionPlan::class);

        return Inertia::render('Platform/Plans/Index', [
            'plans' => SubscriptionPlan::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SubscriptionPlan::class);

        return Inertia::render('Platform/Plans/Form', [
            'plan' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SubscriptionPlan::class);

        $validated = $this->validated($request);

        SubscriptionPlan::query()->create($validated);

        return redirect()->route('platform.plans.index')->with('success', __('platform.plan_created'));
    }

    public function edit(SubscriptionPlan $plan): Response
    {
        $this->authorize('update', $plan);

        return Inertia::render('Platform/Plans/Form', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $this->authorize('update', $plan);

        $plan->update($this->validated($request, $plan));

        return redirect()->route('platform.plans.index')->with('success', __('platform.plan_updated'));
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $plan->delete();

        return redirect()->route('platform.plans.index')->with('success', __('platform.plan_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:64', 'alpha_dash',
                Rule::unique('subscription_plans', 'slug')->ignore($plan?->getKey()),
            ],
            'price_cents' => ['required', 'integer', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'array'],
            'features.pos' => ['boolean'],
            'features.reports' => ['boolean'],
        ]);

        $validated['features'] = [
            'pos' => $request->boolean('features.pos', true),
            'reports' => $request->boolean('features.reports', true),
        ];

        return $validated;
    }
}
