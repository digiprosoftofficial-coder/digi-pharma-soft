<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\DiscountCoupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PromotionsController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(DiscountCoupon::class, 'discount_coupon');
    }

    public function index(): Response
    {
        return Inertia::render('Promotions/Index', [
            'coupons' => DiscountCoupon::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Promotions/Form', [
            'coupon' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('discount_coupons', 'code')->where('tenant_id', tenant_id()),
            ],
            'percent_off' => ['required', 'numeric', 'min:0', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ]);

        DiscountCoupon::query()->create([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('tenant.promotions.index')->with('success', __('Coupon created.'));
    }

    public function edit(DiscountCoupon $discountCoupon): Response
    {
        return Inertia::render('Promotions/Form', [
            'coupon' => $discountCoupon,
        ]);
    }

    public function update(Request $request, DiscountCoupon $discountCoupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('discount_coupons', 'code')->where('tenant_id', tenant_id())->ignore($discountCoupon->getKey()),
            ],
            'percent_off' => ['required', 'numeric', 'min:0', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $discountCoupon->update([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('tenant.promotions.index')->with('success', __('Coupon updated.'));
    }

    public function destroy(DiscountCoupon $discountCoupon): RedirectResponse
    {
        $discountCoupon->delete();

        return redirect()->route('tenant.promotions.index')->with('success', __('Coupon removed.'));
    }
}
