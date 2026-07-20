<?php

namespace App\Support\Theme;

use App\Domain\Tenant\Models\Tenant;

final class ThemeCatalog
{
    public const CLASSIC_BLUE = 'classic_blue';

    public const EMERALD = 'emerald';

    public const TEAL = 'teal';

    public const INDIGO = 'indigo';

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return array<string, array{id: string, primary: string, primary_rgb: string}>
     */
    public static function definitions(): array
    {
        return [
            self::CLASSIC_BLUE => [
                'id' => self::CLASSIC_BLUE,
                'primary' => '#0d6efd',
                'primary_rgb' => '13, 110, 253',
            ],
            self::EMERALD => [
                'id' => self::EMERALD,
                'primary' => '#059669',
                'primary_rgb' => '5, 150, 105',
            ],
            self::TEAL => [
                'id' => self::TEAL,
                'primary' => '#0d9488',
                'primary_rgb' => '13, 148, 136',
            ],
            self::INDIGO => [
                'id' => self::INDIGO,
                'primary' => '#4f46e5',
                'primary_rgb' => '79, 70, 229',
            ],
        ];
    }

    /**
     * @return list<array{id: string, primary: string, primary_rgb: string, label_key: string}>
     */
    public static function listForUi(): array
    {
        return array_values(array_map(
            fn (array $def) => [
                ...$def,
                'label_key' => 'common.theme_'.$def['id'],
            ],
            self::definitions(),
        ));
    }

    /**
     * @return list<string>
     */
    public static function allowedTemplatesForTenant(?Tenant $tenant): array
    {
        if ($tenant === null) {
            return [self::CLASSIC_BLUE];
        }

        $tenant->loadMissing('activeSubscription.plan');
        $planFeatures = $tenant->activeSubscription?->plan?->features ?? [];
        $fromPlan = $planFeatures['theme_templates'] ?? null;

        if (! is_array($fromPlan) || $fromPlan === []) {
            return [self::CLASSIC_BLUE];
        }

        $allowed = array_values(array_intersect(
            array_map('strval', $fromPlan),
            self::ids(),
        ));

        return $allowed !== [] ? $allowed : [self::CLASSIC_BLUE];
    }

    public static function allowCustomPrimary(?Tenant $tenant): bool
    {
        if ($tenant === null) {
            return false;
        }

        $tenant->loadMissing('activeSubscription.plan');
        $planFeatures = $tenant->activeSubscription?->plan?->features ?? [];

        return (bool) ($planFeatures['allow_custom_primary'] ?? false);
    }

    /**
     * Resolved theme for the current tenant (or platform default).
     *
     * @return array{
     *     template: string,
     *     primary: string,
     *     primary_rgb: string,
     *     allow_custom_primary: bool,
     *     available_templates: list<array{id: string, primary: string, primary_rgb: string, label_key: string}>
     * }
     */
    public static function resolveForTenant(?Tenant $tenant): array
    {
        $allowedIds = self::allowedTemplatesForTenant($tenant);
        $definitions = self::definitions();
        $allowCustom = self::allowCustomPrimary($tenant);

        $requested = is_string($tenant?->settings['theme']['template'] ?? null)
            ? (string) $tenant->settings['theme']['template']
            : self::CLASSIC_BLUE;

        $templateId = in_array($requested, $allowedIds, true)
            ? $requested
            : $allowedIds[0];

        $base = $definitions[$templateId] ?? $definitions[self::CLASSIC_BLUE];
        $primary = $base['primary'];
        $primaryRgb = $base['primary_rgb'];

        $customPrimary = is_string($tenant?->settings['theme']['primary'] ?? null)
            ? strtolower(trim((string) $tenant->settings['theme']['primary']))
            : null;

        if ($allowCustom && self::isValidHex($customPrimary)) {
            $primary = $customPrimary;
            $primaryRgb = self::hexToRgb($customPrimary) ?? $primaryRgb;
        }

        $available = array_values(array_map(
            fn (string $id) => [
                ...$definitions[$id],
                'label_key' => 'common.theme_'.$id,
            ],
            $allowedIds,
        ));

        return [
            'template' => $templateId,
            'primary' => $primary,
            'primary_rgb' => $primaryRgb,
            'allow_custom_primary' => $allowCustom,
            'available_templates' => $available,
        ];
    }

    public static function isValidHex(?string $hex): bool
    {
        if ($hex === null || $hex === '') {
            return false;
        }

        return (bool) preg_match('/^#[0-9a-f]{6}$/i', $hex);
    }

    public static function hexToRgb(string $hex): ?string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return null;
        }

        $int = hexdec($hex);

        return sprintf('%d, %d, %d', ($int >> 16) & 255, ($int >> 8) & 255, $int & 255);
    }

    /**
     * Normalize theme payload from settings form.
     *
     * @param  array{template?: mixed, primary?: mixed}  $incoming
     * @return array{template: string, primary: string|null}
     */
    public static function normalizeTenantTheme(Tenant $tenant, array $incoming): array
    {
        $allowed = self::allowedTemplatesForTenant($tenant);
        $template = is_string($incoming['template'] ?? null) ? (string) $incoming['template'] : self::CLASSIC_BLUE;
        if (! in_array($template, $allowed, true)) {
            $template = $allowed[0];
        }

        $primary = null;
        if (self::allowCustomPrimary($tenant)) {
            $raw = is_string($incoming['primary'] ?? null) ? strtolower(trim((string) $incoming['primary'])) : null;
            $primary = self::isValidHex($raw) ? $raw : null;
        }

        return [
            'template' => $template,
            'primary' => $primary,
        ];
    }
}
