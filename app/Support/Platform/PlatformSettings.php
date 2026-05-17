<?php

namespace App\Support\Platform;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

final class PlatformSettings
{
    private const CACHE_KEY = 'platform.settings';

    /**
     * @return array{
     *   default_trial_days: int,
     *   support_email: string|null,
     *   support_phone: string|null,
     *   sms_provider: string|null,
     *   sms_api_key: string|null,
     *   sms_api_key_set: bool,
     *   feature_flags: array<string, bool>
     * }
     */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $row = self::model();

            return self::present($row);
        });
    }

    /**
     * @param  array{
     *   default_trial_days?: int,
     *   support_email?: string|null,
     *   support_phone?: string|null,
     *   sms_provider?: string|null,
     *   sms_api_key?: string|null,
     *   clear_sms_api_key?: bool,
     *   feature_flags?: array<string, bool>
     * }  $data
     */
    public static function update(array $data): array
    {
        $row = self::model();

        if (array_key_exists('default_trial_days', $data)) {
            $row->default_trial_days = max(1, (int) $data['default_trial_days']);
        }

        if (array_key_exists('support_email', $data)) {
            $row->support_email = $data['support_email'] ?: null;
        }

        if (array_key_exists('support_phone', $data)) {
            $row->support_phone = $data['support_phone'] ?: null;
        }

        if (array_key_exists('sms_provider', $data)) {
            $row->sms_provider = $data['sms_provider'] ?: null;
        }

        if (! empty($data['clear_sms_api_key'])) {
            $row->sms_api_key = null;
        } elseif (array_key_exists('sms_api_key', $data) && $data['sms_api_key'] !== null && $data['sms_api_key'] !== '') {
            $row->sms_api_key = $data['sms_api_key'];
        }

        if (array_key_exists('feature_flags', $data)) {
            $row->feature_flags = $data['feature_flags'];
        }

        $row->save();
        Cache::forget(self::CACHE_KEY);

        return self::present($row->fresh());
    }

    public static function defaultTrialDays(): int
    {
        return (int) self::get()['default_trial_days'];
    }

    public static function defaultFeatureFlags(): array
    {
        return self::get()['feature_flags'];
    }

    private static function model(): PlatformSetting
    {
        return PlatformSetting::query()->firstOrCreate([], [
            'default_trial_days' => 14,
            'feature_flags' => [
                'pos' => true,
                'reports' => true,
                'stock_transfers' => true,
            ],
        ]);
    }

  /**
     * @return array{
     *   default_trial_days: int,
     *   support_email: string|null,
     *   support_phone: string|null,
     *   sms_provider: string|null,
     *   sms_api_key: string|null,
     *   sms_api_key_set: bool,
     *   feature_flags: array<string, bool>
     * }
     */
    private static function present(PlatformSetting $row): array
    {
        $flags = array_merge([
            'pos' => true,
            'reports' => true,
            'stock_transfers' => true,
        ], $row->feature_flags ?? []);

        return [
            'default_trial_days' => (int) $row->default_trial_days,
            'support_email' => $row->support_email,
            'support_phone' => $row->support_phone,
            'sms_provider' => $row->sms_provider,
            'sms_api_key' => null,
            'sms_api_key_set' => filled($row->sms_api_key),
            'feature_flags' => $flags,
        ];
    }
}
