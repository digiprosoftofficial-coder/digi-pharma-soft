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
     *   feature_flags: array<string, bool>,
     *   audit_log_retention_days: int,
     *   compliance_export_retention_days: int,
     *   billing_grace_days: int,
     *   auto_suspend_on_payment_failure: bool,
     *   default_currency: string,
     *   default_locale: string,
     *   default_timezone: string,
     *   default_country_code: string
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
     *   feature_flags?: array<string, bool>,
     *   audit_log_retention_days?: int,
     *   compliance_export_retention_days?: int,
     *   billing_grace_days?: int,
     *   auto_suspend_on_payment_failure?: bool,
     *   default_currency?: string,
     *   default_locale?: string,
     *   default_timezone?: string,
     *   default_country_code?: string
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

        if (array_key_exists('audit_log_retention_days', $data)) {
            $row->audit_log_retention_days = max(30, (int) $data['audit_log_retention_days']);
        }

        if (array_key_exists('compliance_export_retention_days', $data)) {
            $row->compliance_export_retention_days = max(1, (int) $data['compliance_export_retention_days']);
        }

        if (array_key_exists('billing_grace_days', $data)) {
            $row->billing_grace_days = max(0, (int) $data['billing_grace_days']);
        }

        if (array_key_exists('auto_suspend_on_payment_failure', $data)) {
            $row->auto_suspend_on_payment_failure = (bool) $data['auto_suspend_on_payment_failure'];
        }

        if (array_key_exists('default_currency', $data)) {
            $row->default_currency = strtoupper((string) $data['default_currency']) ?: 'BDT';
        }

        if (array_key_exists('default_locale', $data)) {
            $locale = (string) $data['default_locale'];
            $row->default_locale = in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
        }

        if (array_key_exists('default_timezone', $data)) {
            $row->default_timezone = (string) $data['default_timezone'] ?: 'Asia/Dhaka';
        }

        if (array_key_exists('default_country_code', $data)) {
            $row->default_country_code = strtoupper((string) $data['default_country_code']) ?: 'BD';
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

    public static function billingGraceDays(): int
    {
        return (int) self::get()['billing_grace_days'];
    }

    public static function autoSuspendOnPaymentFailure(): bool
    {
        return (bool) self::get()['auto_suspend_on_payment_failure'];
    }

    public static function defaultCurrency(): string
    {
        return (string) self::get()['default_currency'];
    }

    public static function defaultLocale(): string
    {
        return (string) self::get()['default_locale'];
    }

    /**
     * @return array{locale: string, timezone: string, country_code: string, currency: string}
     */
    public static function defaultTenantSettings(): array
    {
        $settings = self::get();

        return [
            'locale' => $settings['default_locale'],
            'timezone' => $settings['default_timezone'],
            'country_code' => $settings['default_country_code'],
            'currency' => $settings['default_currency'],
        ];
    }

    private static function model(): PlatformSetting
    {
        return PlatformSetting::query()->firstOrCreate([], [
            'default_trial_days' => 14,
            'feature_flags' => [
                'pos' => true,
                'reports' => true,
                'stock_transfers' => true,
                'package_sales' => false,
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
     *   feature_flags: array<string, bool>,
     *   audit_log_retention_days: int,
     *   compliance_export_retention_days: int,
     *   billing_grace_days: int,
     *   auto_suspend_on_payment_failure: bool,
     *   default_currency: string,
     *   default_locale: string,
     *   default_timezone: string,
     *   default_country_code: string
     * }
     */
    private static function present(PlatformSetting $row): array
    {
        $flags = array_merge([
            'pos' => true,
            'reports' => true,
            'stock_transfers' => true,
            'package_sales' => false,
        ], $row->feature_flags ?? []);

        return [
            'default_trial_days' => (int) $row->default_trial_days,
            'support_email' => $row->support_email,
            'support_phone' => $row->support_phone,
            'sms_provider' => $row->sms_provider,
            'sms_api_key' => null,
            'sms_api_key_set' => filled($row->sms_api_key),
            'feature_flags' => $flags,
            'audit_log_retention_days' => (int) ($row->audit_log_retention_days ?? 365),
            'compliance_export_retention_days' => (int) ($row->compliance_export_retention_days ?? 7),
            'billing_grace_days' => (int) ($row->billing_grace_days ?? 7),
            'auto_suspend_on_payment_failure' => (bool) ($row->auto_suspend_on_payment_failure ?? true),
            'default_currency' => (string) ($row->default_currency ?? 'BDT'),
            'default_locale' => (string) ($row->default_locale ?? 'en'),
            'default_timezone' => (string) ($row->default_timezone ?? 'Asia/Dhaka'),
            'default_country_code' => (string) ($row->default_country_code ?? 'BD'),
        ];
    }
}
