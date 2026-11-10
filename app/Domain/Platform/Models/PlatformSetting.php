<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PlatformSetting extends Model
{
    protected $fillable = [
        'default_trial_days',
        'support_email',
        'support_phone',
        'sms_provider',
        'sms_api_key',
        'feature_flags',
        'audit_log_retention_days',
        'compliance_export_retention_days',
        'billing_grace_days',
        'auto_suspend_on_payment_failure',
        'default_currency',
        'default_locale',
        'default_timezone',
        'default_country_code',
        'self_service_enabled',
        'self_service_requires_approval',
        'show_try_live_demo',
        'payment_instructions',
        'bkash_merchant_number',
        'nagad_merchant_number',
        'bank_transfer_details',
        'whatsapp_provider',
        'whatsapp_api_key',
        'notification_preferences',
    ];

    protected function casts(): array
    {
        return [
            'default_trial_days' => 'integer',
            'feature_flags' => 'array',
            'audit_log_retention_days' => 'integer',
            'compliance_export_retention_days' => 'integer',
            'billing_grace_days' => 'integer',
            'auto_suspend_on_payment_failure' => 'boolean',
            'self_service_enabled' => 'boolean',
            'self_service_requires_approval' => 'boolean',
            'show_try_live_demo' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function smsApiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                return Crypt::encryptString($value);
            },
        );
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function whatsappApiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                return Crypt::encryptString($value);
            },
        );
    }
}
