<?php

namespace App\Support\Platform;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class MarketingSiteSettings
{
    private const CACHE_KEY = 'platform.marketing_settings';

    /**
     * @return array{
     *   site_name: string,
     *   tagline: string,
     *   product_of: string,
     *   seo_title: string,
     *   seo_description: string,
     *   seo_keywords: string,
     *   logo_url: string,
     *   icon_url: string,
     *   og_image_url: string,
     *   logo_path: string|null,
     *   icon_path: string|null,
     *   og_image_path: string|null
     * }
     */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $row = PlatformSetting::query()->first();
            $stored = is_array($row?->marketing_settings) ? $row->marketing_settings : [];

            return self::present($stored);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function update(array $data, ?UploadedFile $logo = null, ?UploadedFile $icon = null, ?UploadedFile $ogImage = null): array
    {
        $row = PlatformSetting::query()->firstOrCreate([], [
            'default_trial_days' => 14,
            'feature_flags' => [
                'pos' => true,
                'reports' => true,
                'stock_transfers' => true,
                'package_sales' => false,
            ],
        ]);

        $current = is_array($row->marketing_settings) ? $row->marketing_settings : [];
        $defaults = self::defaults();

        $next = [
            'site_name' => (string) ($data['site_name'] ?? $current['site_name'] ?? $defaults['site_name']),
            'tagline' => (string) ($data['tagline'] ?? $current['tagline'] ?? $defaults['tagline']),
            'product_of' => (string) ($data['product_of'] ?? $current['product_of'] ?? $defaults['product_of']),
            'seo_title' => (string) ($data['seo_title'] ?? $current['seo_title'] ?? $defaults['seo_title']),
            'seo_description' => (string) ($data['seo_description'] ?? $current['seo_description'] ?? $defaults['seo_description']),
            'seo_keywords' => (string) ($data['seo_keywords'] ?? $current['seo_keywords'] ?? $defaults['seo_keywords']),
            'logo_path' => $current['logo_path'] ?? null,
            'icon_path' => $current['icon_path'] ?? null,
            'og_image_path' => $current['og_image_path'] ?? null,
        ];

        if ($logo) {
            $next['logo_path'] = self::storeUpload($logo, 'logo');
        }
        if ($icon) {
            $next['icon_path'] = self::storeUpload($icon, 'icon');
        }
        if ($ogImage) {
            $next['og_image_path'] = self::storeUpload($ogImage, 'og');
        }

        $row->marketing_settings = $next;
        $row->save();
        Cache::forget(self::CACHE_KEY);

        return self::present($next);
    }

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => 'SoftoPharma',
            'tagline' => 'Your Pharmacy. Smarter Management.',
            'product_of' => 'A Product of DigiProSoft',
            'seo_title' => 'SoftoPharma – Pharmacy Management Software Bangladesh | DigiProSoft',
            'seo_description' => 'SoftoPharma is pharmacy management software for Bangladesh: POS sales, medicine inventory, batch expiry, purchases, multi-branch, and accounts — built by DigiProSoft.',
            'seo_keywords' => 'pharmacy management software, pharmacy POS Bangladesh, medicine inventory software, pharmacy billing software, expiry management, DigiProSoft, SoftoPharma',
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function present(array $stored): array
    {
        $defaults = self::defaults();
        $logoPath = $stored['logo_path'] ?? null;
        $iconPath = $stored['icon_path'] ?? null;
        $ogPath = $stored['og_image_path'] ?? null;

        return [
            'site_name' => (string) ($stored['site_name'] ?? $defaults['site_name']),
            'tagline' => (string) ($stored['tagline'] ?? $defaults['tagline']),
            'product_of' => (string) ($stored['product_of'] ?? $defaults['product_of']),
            'seo_title' => (string) ($stored['seo_title'] ?? $defaults['seo_title']),
            'seo_description' => (string) ($stored['seo_description'] ?? $defaults['seo_description']),
            'seo_keywords' => (string) ($stored['seo_keywords'] ?? $defaults['seo_keywords']),
            'logo_path' => $logoPath,
            'icon_path' => $iconPath,
            'og_image_path' => $ogPath,
            'logo_url' => self::publicUrl($logoPath, '/brand/softopharma-logo.png'),
            'icon_url' => self::publicUrl($iconPath, '/brand/softopharma-icon.png'),
            'og_image_url' => self::publicUrl(
                $ogPath,
                self::publicUrl($logoPath, '/brand/softopharma-logo.png'),
            ),
        ];
    }

    private static function storeUpload(UploadedFile $file, string $kind): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $name = 'marketing-'.$kind.'-'.now()->format('YmdHis').'.'.$ext;

        return $file->storeAs('brand', $name, 'public');
    }

    private static function publicUrl(?string $storedPath, string $fallback): string
    {
        if ($storedPath) {
            if (str_starts_with($storedPath, '/')) {
                return $storedPath;
            }

            return Storage::disk('public')->url($storedPath);
        }

        return $fallback;
    }
}
