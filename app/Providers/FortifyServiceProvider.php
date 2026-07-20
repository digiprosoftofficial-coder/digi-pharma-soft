<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\Fortify\InertiaLoginResponse;
use App\Http\Responses\Fortify\InertiaLogoutResponse;
use App\Http\Responses\Fortify\InertiaPasswordConfirmedResponse;
use App\Http\Responses\Fortify\InertiaRegisterResponse;
use App\Http\Responses\Fortify\InertiaTwoFactorLoginResponse;
use App\Http\Responses\Fortify\InertiaVerifyEmailResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, InertiaLoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, InertiaLogoutResponse::class);
        $this->app->singleton(RegisterResponseContract::class, InertiaRegisterResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, InertiaVerifyEmailResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, InertiaTwoFactorLoginResponse::class);
        $this->app->singleton(PasswordConfirmedResponseContract::class, InertiaPasswordConfirmedResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => Inertia::render('Auth/Login'));
        Fortify::registerView(fn () => Inertia::render('Auth/Register'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword'));
        Fortify::resetPasswordView(fn ($request) => Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'invite' => $request->boolean('invite'),
        ]));
        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail'));
        Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('Auth/TwoFactorChallenge'));

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input(Fortify::username()));
            $throttleKey = Str::transliterate($email.'|'.$request->ip());
            $maxAttempts = $this->isElevatedLoginTarget($email) ? 3 : 5;

            return Limit::perMinute($maxAttempts)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id').$request->ip());
        });

        RateLimiter::for('platform-login', function (Request $request) {
            $email = Str::lower((string) $request->input(Fortify::username()));
            $throttleKey = Str::transliterate($email.'|'.$request->ip().'|platform');

            return Limit::perMinute(3)->by($throttleKey);
        });
    }

    private function isElevatedLoginTarget(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        return User::query()
            ->where('email', $email)
            ->where(function ($query): void {
                $query->where('is_platform_super_admin', true)
                    ->orWhereNull('tenant_id');
            })
            ->exists();
    }
}
