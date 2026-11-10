<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpVerification extends Model
{
    public const PURPOSE_REGISTRATION = 'registration';

    protected $fillable = [
        'phone',
        'email',
        'purpose',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'session_token',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return array{0: self, 1: string}
     */
    public static function issue(string $email, string $purpose = self::PURPOSE_REGISTRATION, int $ttlMinutes = 10): array
    {
        $code = (string) random_int(100000, 999999);

        $row = static::query()->create([
            'email' => Str::lower(trim($email)),
            'phone' => '',
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'session_token' => Str::random(40),
        ]);

        return [$row, $code];
    }

    public function matches(string $code): bool
    {
        return Hash::check($code, $this->code_hash);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
