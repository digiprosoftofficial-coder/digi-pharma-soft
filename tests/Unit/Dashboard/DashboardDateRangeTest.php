<?php

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\DashboardDateRange;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class DashboardDateRangeTest extends TestCase
{
    public function test_today_bounds(): void
    {
        $now = CarbonImmutable::parse('2026-07-25 15:00:00');
        [$from, $to] = DashboardDateRange::boundsForPreset('today', $now);

        $this->assertSame('2026-07-25', $from->toDateString());
        $this->assertSame('2026-07-25', $to->toDateString());
    }

    public function test_last_7_days_includes_today(): void
    {
        $now = CarbonImmutable::parse('2026-07-25 15:00:00');
        [$from, $to] = DashboardDateRange::boundsForPreset('last_7_days', $now);

        $this->assertSame('2026-07-19', $from->toDateString());
        $this->assertSame('2026-07-25', $to->toDateString());
    }

    public function test_current_financial_year_starts_in_july(): void
    {
        $now = CarbonImmutable::parse('2026-07-25 12:00:00');
        [$from, $to] = DashboardDateRange::boundsForPreset('current_financial_year', $now);

        $this->assertSame('2026-07-01', $from->toDateString());
        $this->assertSame('2026-07-25', $to->toDateString());
    }

    public function test_last_financial_year_full_period(): void
    {
        $now = CarbonImmutable::parse('2026-07-25 12:00:00');
        [$from, $to] = DashboardDateRange::boundsForPreset('last_financial_year', $now);

        $this->assertSame('2025-07-01', $from->toDateString());
        $this->assertSame('2026-06-30', $to->toDateString());
    }
}
