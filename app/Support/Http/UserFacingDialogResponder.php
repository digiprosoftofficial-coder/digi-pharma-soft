<?php

namespace App\Support\Http;

use App\Models\User;
use App\Support\Auth\PostAuthRedirect;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class UserFacingDialogResponder
{
    /**
     * @var list<int>
     */
    private const STATUSES = [403, 404, 419, 429, 500, 503];

    public function respond(Response $response, Throwable $exception, Request $request): Response
    {
        if (! $this->shouldConvert($response, $request)) {
            return $response;
        }

        $dialog = UserFacingDialog::from($response->getStatusCode(), $exception);

        if ($this->homeWouldLoop($request)) {
            return Inertia::render('Shared/AppNotice', [
                'dialog' => $dialog,
            ])->toResponse($request)->setStatusCode(200);
        }

        return redirect()
            ->to($this->redirectTarget($request))
            ->with(UserFacingDialog::SESSION_KEY, $dialog);
    }

    private function shouldConvert(Response $response, Request $request): bool
    {
        if ($response->isRedirection()) {
            return false;
        }

        if (! in_array($response->getStatusCode(), self::STATUSES, true)) {
            return false;
        }

        if ($request->is('api/*') || $request->is('up')) {
            return false;
        }

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return false;
        }

        return true;
    }

    private function redirectTarget(Request $request): string
    {
        $home = $this->homeUrl($request);
        $previous = url()->previous($home);
        $current = rtrim($request->fullUrl(), '/');
        $previousNorm = rtrim($previous, '/');

        if ($previousNorm !== $current && $this->isInternalUrl($previous) && ! $this->isBareRoot($previous)) {
            return $previous;
        }

        return $home;
    }

    private function homeWouldLoop(Request $request): bool
    {
        $home = rtrim(url($this->homeUrl($request)), '/');

        return rtrim($request->fullUrl(), '/') === $home;
    }

    private function homeUrl(Request $request): string
    {
        $user = $request->user();
        if ($user instanceof User) {
            return PostAuthRedirect::dashboardPath($user);
        }

        return route('login', absolute: false);
    }

    private function isInternalUrl(string $url): bool
    {
        $app = rtrim((string) config('app.url'), '/');
        if ($app === '') {
            return str_starts_with($url, '/');
        }

        return str_starts_with($url, $app) || str_starts_with($url, '/');
    }

    private function isBareRoot(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        return $path === null || $path === '' || $path === '/';
    }
}
