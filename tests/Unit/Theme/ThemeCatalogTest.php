<?php

namespace Tests\Unit\Theme;

use App\Support\Theme\ThemeCatalog;
use PHPUnit\Framework\TestCase;

final class ThemeCatalogTest extends TestCase
{
    public function test_definitions_include_four_templates(): void
    {
        $ids = ThemeCatalog::ids();

        $this->assertSame(
            ['classic_blue', 'emerald', 'teal', 'indigo'],
            $ids,
        );
    }

    public function test_hex_to_rgb(): void
    {
        $this->assertSame('13, 110, 253', ThemeCatalog::hexToRgb('#0d6efd'));
        $this->assertSame('5, 150, 105', ThemeCatalog::hexToRgb('#059669'));
    }

    public function test_resolve_defaults_without_tenant(): void
    {
        $theme = ThemeCatalog::resolveForTenant(null);

        $this->assertSame('classic_blue', $theme['template']);
        $this->assertSame('#0d6efd', $theme['primary']);
        $this->assertFalse($theme['allow_custom_primary']);
        $this->assertCount(1, $theme['available_templates']);
    }
}
