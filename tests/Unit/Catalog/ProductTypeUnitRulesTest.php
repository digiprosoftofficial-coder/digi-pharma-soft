<?php

namespace Tests\Unit\Catalog;

use App\Support\Catalog\ProductTypeUnitRules;
use PHPUnit\Framework\TestCase;

class ProductTypeUnitRulesTest extends TestCase
{
    public function test_tablet_allows_strip(): void
    {
        $units = ProductTypeUnitRules::sellUnitsFor('tablet');

        $this->assertContains('strip', $units);
        $this->assertSame('strip', ProductTypeUnitRules::defaultBaseUnit('tablet'));
    }

    public function test_syrup_excludes_strip(): void
    {
        $units = ProductTypeUnitRules::sellUnitsFor('syrup');

        $this->assertNotContains('strip', $units);
        $this->assertContains('piece', $units);
        $this->assertSame('piece', ProductTypeUnitRules::defaultBaseUnit('syrup'));
    }

    public function test_legacy_strip_on_syrup_included_when_requested(): void
    {
        $units = ProductTypeUnitRules::sellUnitsFor('syrup', ['strip']);

        $this->assertContains('strip', $units);
    }
}
