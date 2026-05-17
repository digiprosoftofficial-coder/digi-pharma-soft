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
    ];

    protected function casts(): array
    {
        return [
            'default_trial_days' => 'integer',
            'feature_flags' => 'array',
        ];
    }
}
