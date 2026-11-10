<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Models\OtpVerification;
use App\Mail\RegistrationOtpMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OtpService
{
    /**
     * @return array{0: OtpVerification, 1: string}
     */
    public function send(string $email, string $purpose = OtpVerification::PURPOSE_REGISTRATION): array
    {
        $email = Str::lower(trim($email));

        OtpVerification::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        [$row, $code] = OtpVerification::issue($email, $purpose);

        Mail::to($email)->send(new RegistrationOtpMail($code));

        Log::info('OTP issued via email', [
            'email' => $email,
            'purpose' => $purpose,
            'code' => app()->environment('production') ? '[redacted]' : $code,
        ]);

        if (! app()->environment('production')) {
            session()->flash('otp_debug_code', $code);
        }

        return [$row, $code];
    }

    public function verify(string $email, string $code, string $purpose = OtpVerification::PURPOSE_REGISTRATION): OtpVerification
    {
        $email = Str::lower(trim($email));

        $row = OtpVerification::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if (! $row || $row->isExpired()) {
            throw ValidationException::withMessages([
                'code' => __('platform.otp_expired'),
            ]);
        }

        if ($row->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => __('platform.otp_too_many_attempts'),
            ]);
        }

        if (! $row->matches($code)) {
            $row->increment('attempts');
            throw ValidationException::withMessages([
                'code' => __('platform.otp_invalid'),
            ]);
        }

        $row->forceFill(['verified_at' => now()])->save();

        return $row->fresh();
    }
}
