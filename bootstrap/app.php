<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/stripe/billing',
        ]);

        $middleware->web(append: [
            \App\Support\Middleware\SetLocale::class,
            \App\Support\Middleware\EnsureTenantContext::class,
            \App\Support\Middleware\EnsureBranchContext::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'tenant.staff' => \App\Support\Middleware\EnsureTenantStaff::class,
            'tenant.subscription' => \App\Support\Middleware\EnsureTenantSubscriptionActive::class,
            'platform.2fa' => \App\Support\Middleware\EnsurePlatformTwoFactorEnabled::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
