<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('platform.email_otp_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.registration-otp',
            with: [
                'code' => $this->code,
            ],
        );
    }
}
