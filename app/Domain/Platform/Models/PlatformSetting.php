<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

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
        ];
    }
}
