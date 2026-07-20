<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\PostAuthRedirect;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Fortify;

final class PlatformLoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'platformOnly' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            Fortify::username() => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $email = Str::lower((string) $request->input(Fortify::username()));
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey($request));
            event(new Failed('web', $user, [
                Fortify::username() => $email,
                'password' => $request->input('password'),
            ]));

            throw ValidationException::withMessages([
                Fortify::username() => [__('auth.failed')],
            ]);
        }

        if (! $user->shouldUsePlatformDashboard()) {
            RateLimiter::hit($this->throttleKey($request));
            event(new Failed('web', $user, [
                Fortify::username() => $email,
                'password' => $request->input('password'),
            ]));

            throw ValidationException::withMessages([
                Fortify::username() => [__('auth.platform_login_denied')],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        if (config('platform.2fa_required') && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            TwoFactorAuthenticationChallenged::dispatch($user);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        PostAuthRedirect::forgetTenantDashboardIntendedWhenPlatformOperator($request, $user);

        return redirect()->intended(PostAuthRedirect::dashboardPath($user));
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 3)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            Fortify::username() => [__('auth.throttle', ['seconds' => $seconds])],
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip().'|platform');
    }
}
