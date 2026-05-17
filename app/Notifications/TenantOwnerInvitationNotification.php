<?php

namespace App\Notifications;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TenantOwnerInvitationNotification extends Notification
{

    public function __construct(
        private readonly Tenant $tenant,
        private readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false)).'?invite=1';

        return (new MailMessage)
            ->subject(__('platform.owner_invite_subject', ['name' => $this->tenant->name]))
            ->greeting(__('platform.owner_invite_greeting', ['name' => $notifiable->name]))
            ->line(__('platform.owner_invite_line', ['pharmacy' => $this->tenant->name]))
            ->action(__('platform.owner_invite_action'), $url)
            ->line(__('platform.owner_invite_expiry'));
    }
}
