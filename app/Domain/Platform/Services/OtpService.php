<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Models\OtpVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class OtpService
{
    /**
     * @return array{0: OtpVerification, 1: string}
     */
    public function send(string $phone, string $purpose = OtpVerification::PURPOSE_REGISTRATION): array
    {
        OtpVerification::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        [$row, $code] = OtpVerification::issue($phone, $purpose);

        // Production: wire SMS/WhatsApp provider. Local/dev: log the code.
        Log::info('OTP issued', [
            'phone' => $phone,
            'purpose' => $purpose,
            'code' => app()->environment('production') ? '[redacted]' : $code,
        ]);

        if (! app()->environment('production')) {
            session()->flash('otp_debug_code', $code);
        }

        return [$row, $code];
    }

    public function verify(string $phone, string $code, string $purpose = OtpVerification::PURPOSE_REGISTRATION): OtpVerification
    {
        $row = OtpVerification::query()
            ->where('phone', $phone)
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
