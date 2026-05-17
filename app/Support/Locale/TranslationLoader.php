<?php

namespace App\Support\Locale;

final class TranslationLoader
{
    /** @var list<string> */
    private const FILES = ['common', 'platform', 'tenant_nav'];

    /**
     * @return array<string, string>
     */
    public static function forLocale(string $locale): array
    {
        $out = [];

        foreach (self::FILES as $file) {
            $path = lang_path("{$locale}/{$file}.php");
            if (! is_file($path)) {
                continue;
            }
            /** @var array<string, string> $chunk */
            $chunk = require $path;
            foreach ($chunk as $key => $value) {
                $out["{$file}.{$key}"] = $value;
            }
        }

        return $out;
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    public static function availableLocales(): array
    {
        return [
            ['code' => 'en', 'label' => 'English'],
            ['code' => 'bn', 'label' => 'বাংলা'],
        ];
    }
}
