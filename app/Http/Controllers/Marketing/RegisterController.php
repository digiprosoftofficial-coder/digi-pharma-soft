<?php

namespace App\Http\Controllers\Marketing;

use App\Domain\Billing\Models\PlatformPayment;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Platform\Services\SelfServiceRegistrationService;
use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

final class RegisterController extends Controller
{
    public function __construct(private readonly SelfServiceRegistrationService $registration) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (! PlatformSettings::selfServiceEnabled()) {
            return redirect()->route('marketing.demo');
        }

        $plan = null;
        if ($request->filled('plan')) {
            $plan = SubscriptionPlan::query()->active()->where('slug', $request->string('plan'))->first();
        }

        $settings = PlatformSettings::get();

        return Inertia::render('Marketing/Register', [
            'plans' => SubscriptionPlan::query()->active()->orderBy('sort_order')->get(),
            'selectedPlanId' => $plan?->getKey(),
            'selectedInterval' => $request->string('interval')->toString() ?: 'monthly',
            'currency' => PlatformSettings::defaultCurrency(),
            'paymentMethods' => PlatformPayment::METHODS,
            'paymentInfo' => [
                'instructions' => $settings['payment_instructions'],
                'bkash' => $settings['bkash_merchant_number'],
                'nagad' => $settings['nagad_merchant_number'],
                'bank' => $settings['bank_transfer_details'],
            ],
            'otpDebugCode' => session('otp_debug_code'),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_email' => ['required', 'email', 'max:255'],
        ]);

        $otp = $this->registration->sendOtp($data['owner_email']);

        return back()->with([
            'success' => __('platform.otp_sent'),
            'otp_session_token' => $otp->session_token,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'owner_email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $otp = $this->registration->verifyOtp($data['owner_email'], $data['code']);

        return back()->with([
            'success' => __('platform.otp_verified'),
            'otp_session_token' => $otp->session_token,
            'email_verified' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pharmacy_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'otp_session_token' => ['required', 'string'],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'start_trial' => ['boolean'],
            'payment_method' => ['nullable', Rule::in(PlatformPayment::METHODS)],
            'transaction_reference' => ['nullable', 'string', 'max:128'],
            'branch_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'address' => ['nullable', 'string', 'max:2000'],
        ], [
            'phone.regex' => __('platform.phone_invalid'),
        ]);

        $result = $this->registration->register($data);
        $startTrial = ! empty($data['start_trial']);

        if ($startTrial) {
            $owner = $result['tenant']->users()->first();
            if ($owner) {
                Auth::login($owner);
                $request->session()->regenerate();
            }

            return redirect()
                ->route('tenant.dashboard')
                ->with('success', __('platform.registration_complete'));
        }

        return redirect()
            ->route('marketing.register.pending')
            ->with('success', __('platform.registration_pending_payment'));
    }

    public function pending(): Response
    {
        return Inertia::render('Marketing/RegisterPending', [
            'support' => [
                'email' => PlatformSettings::get()['support_email'],
                'phone' => PlatformSettings::get()['support_phone'],
            ],
        ]);
    }
}
