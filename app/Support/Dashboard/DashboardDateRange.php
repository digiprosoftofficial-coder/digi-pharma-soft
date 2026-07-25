<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Preset date ranges for the owner dashboard filter dropdown.
 */
final class DashboardDateRange
{
    public const PRESETS = [
        'today',
        'yesterday',
        'last_7_days',
        'last_30_days',
        'this_month',
        'last_month',
        'this_month_last_year',
        'this_year',
        'last_year',
        'current_financial_year',
        'last_financial_year',
        'custom',
    ];

    public function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly ?string $customFrom = null,
        public readonly ?string $customTo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $key = (string) $request->input('range', 'today');
        if (! in_array($key, self::PRESETS, true)) {
            $key = 'today';
        }

        $now = now()->toImmutable();

        if ($key === 'custom') {
            $fromInput = $request->date('date_from');
            $toInput = $request->date('date_to');

            $from = $fromInput
                ? CarbonImmutable::parse($fromInput->toDateString())->startOfDay()
                : $now->subDays(30)->startOfDay();
            $to = $toInput
                ? CarbonImmutable::parse($toInput->toDateString())->endOfDay()
                : $now->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
            }

            return new self(
                key: 'custom',
                from: $from,
                to: $to,
                customFrom: $from->toDateString(),
                customTo: $to->toDateString(),
            );
        }

        [$from, $to] = self::boundsForPreset($key, $now);

        return new self(key: $key, from: $from, to: $to);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function boundsForPreset(string $key, ?CarbonImmutable $now = null): array
    {
        $now = $now ?? now()->toImmutable();

        return match ($key) {
            'yesterday' => [
                $now->subDay()->startOfDay(),
                $now->subDay()->endOfDay(),
            ],
            'last_7_days' => [
                $now->subDays(6)->startOfDay(),
                $now->endOfDay(),
            ],
            'last_30_days' => [
                $now->subDays(29)->startOfDay(),
                $now->endOfDay(),
            ],
            'this_month' => [
                $now->startOfMonth()->startOfDay(),
                $now->endOfDay(),
            ],
            'last_month' => [
                $now->subMonthNoOverflow()->startOfMonth()->startOfDay(),
                $now->subMonthNoOverflow()->endOfMonth()->endOfDay(),
            ],
            'this_month_last_year' => [
                $now->subYear()->startOfMonth()->startOfDay(),
                $now->subYear()->endOfMonth()->endOfDay(),
            ],
            'this_year' => [
                $now->startOfYear()->startOfDay(),
                $now->endOfDay(),
            ],
            'last_year' => [
                $now->subYear()->startOfYear()->startOfDay(),
                $now->subYear()->endOfYear()->endOfDay(),
            ],
            'current_financial_year' => self::financialYearBounds($now, 0),
            'last_financial_year' => self::financialYearBounds($now, 1),
            default => [ // today
                $now->startOfDay(),
                $now->endOfDay(),
            ],
        };
    }

    /**
     * Bangladesh-style financial year: 1 July → 30 June.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function financialYearBounds(CarbonImmutable $now, int $yearsAgo): array
    {
        $fyStartYear = $now->month >= 7 ? $now->year : $now->year - 1;
        $fyStartYear -= $yearsAgo;

        $from = CarbonImmutable::create($fyStartYear, 7, 1)->startOfDay();
        $to = CarbonImmutable::create($fyStartYear + 1, 6, 30)->endOfDay();

        if ($yearsAgo === 0 && $to->greaterThan($now)) {
            $to = $now->endOfDay();
        }

        return [$from, $to];
    }

    /**
     * @return array{key: string, date_from: string, date_to: string, label_from: string, label_to: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'date_from' => $this->from->toDateString(),
            'date_to' => $this->to->toDateString(),
            'label_from' => $this->from->toDateString(),
            'label_to' => $this->to->toDateString(),
        ];
    }

    public function queryParams(): array
    {
        $params = ['range' => $this->key];

        if ($this->key === 'custom') {
            $params['date_from'] = $this->customFrom ?? $this->from->toDateString();
            $params['date_to'] = $this->customTo ?? $this->to->toDateString();
        }

        return $params;
    }
}
