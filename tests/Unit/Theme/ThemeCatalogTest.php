<?php

namespace Tests\Unit\Theme;

use App\Support\Theme\ThemeCatalog;
use PHPUnit\Framework\TestCase;

final class ThemeCatalogTest extends TestCase
{
    public function test_definitions_include_softo_green_default(): void
    {
        $ids = ThemeCatalog::ids();

        $this->assertSame(
            ['softo_green', 'classic_blue', 'emerald', 'teal', 'indigo'],
            $ids,
        );
        $this->assertSame(ThemeCatalog::SOFTO_GREEN, ThemeCatalog::DEFAULT);
        $this->assertSame('#287a57', ThemeCatalog::DEFAULT_PRIMARY);
    }

    public function test_hex_to_rgb(): void
    {
        $this->assertSame('40, 122, 87', ThemeCatalog::hexToRgb('#287a57'));
        $this->assertSame('13, 110, 253', ThemeCatalog::hexToRgb('#0d6efd'));
        $this->assertSame('5, 150, 105', ThemeCatalog::hexToRgb('#059669'));
    }

    public function test_resolve_defaults_without_tenant(): void
    {
        $theme = ThemeCatalog::resolveForTenant(null);

        $this->assertSame('softo_green', $theme['template']);
        $this->assertSame('#287a57', $theme['primary']);
        $this->assertFalse($theme['allow_custom_primary']);
        $this->assertCount(1, $theme['available_templates']);
    }
}
