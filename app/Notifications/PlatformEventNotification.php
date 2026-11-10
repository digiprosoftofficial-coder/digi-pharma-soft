<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformEventNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Database is sync so the bell updates immediately; mail can still fail independently.
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->line('Event: '.$this->event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $url = null;
        if (! empty($this->data['demo_id'])) {
            $url = '/platform/demos/'.$this->data['demo_id'];
        } elseif (! empty($this->data['lead_id'])) {
            $url = '/platform/leads/'.$this->data['lead_id'];
        } elseif (! empty($this->data['payment_id'])) {
            $url = '/platform/payments';
        }

        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'url' => $url,
        ];
    }
}
