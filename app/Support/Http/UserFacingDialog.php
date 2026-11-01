<?php

namespace App\Support\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class UserFacingDialog
{
    public const SESSION_KEY = 'app_dialog';

    /**
     * @return array{type:string,title:string,message:string,action_label:string,action_href:?string,action_method:?string}
     */
    public static function from(int $status, ?Throwable $exception = null): array
    {
        $message = self::exceptionMessage($exception);

        $dialog = match ($status) {
            403 => [
                'type' => 'forbidden',
                'title' => __('errors.forbidden_title'),
                'message' => $message ?? __('errors.forbidden_body'),
            ],
            404 => [
                'type' => 'not_found',
                'title' => __('errors.not_found_title'),
                'message' => $message ?? __('errors.not_found_body'),
            ],
            419 => [
                'type' => 'expired',
                'title' => __('errors.expired_title'),
                'message' => $message ?? __('errors.expired_body'),
            ],
            429 => [
                'type' => 'too_many',
                'title' => __('errors.too_many_title'),
                'message' => $message ?? __('errors.too_many_body'),
            ],
            default => [
                'type' => 'error',
                'title' => __('errors.server_title'),
                'message' => __('errors.server_body'),
            ],
        };

        $isSubscription = $message === __('platform.subscription_inactive');

        return [
            ...$dialog,
            'action_label' => $isSubscription ? __('common.logout') : __('errors.got_it'),
            'action_href' => $isSubscription ? '/logout' : null,
            'action_method' => $isSubscription ? 'post' : null,
        ];
    }

    private static function exceptionMessage(?Throwable $exception): ?string
    {
        if ($exception === null) {
            return null;
        }

        $raw = '';
        if ($exception instanceof HttpExceptionInterface) {
            $raw = trim($exception->getMessage());
        } elseif ($exception instanceof AuthorizationException) {
            $raw = trim($exception->getMessage());
        }

        if ($raw === '') {
            return null;
        }

        $generic = [
            'Forbidden',
            'Unauthorized',
            'Not Found',
            'This action is unauthorized.',
        ];

        if (in_array($raw, $generic, true)) {
            return null;
        }

        if (str_contains($raw, '\\') || str_contains($raw, 'Stack trace') || str_contains($raw, 'SQLSTATE')) {
            return null;
        }

        return $raw;
    }
}
