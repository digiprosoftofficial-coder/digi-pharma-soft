<?php

namespace App\Support\Catalog;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rank catalog/product search hits so brand/name matches beat substring hits
 * in generic names (e.g. "napa" → Napa before Napadisylate).
 */
final class CatalogSearchRelevance
{
    /**
     * @param  array{
     *     name?:string|null,
     *     generic_name?:string|null,
     *     sku?:string|null,
     *     barcode?:string|null,
     *     strength?:string|null,
     *     manufacturer_name?:string|null
     * }  $fields
     */
    public static function score(string $term, array $fields): int
    {
        $q = Str::lower(trim($term));
        if ($q === '') {
            return 0;
        }

        $name = Str::lower(trim((string) ($fields['name'] ?? '')));
        $generic = Str::lower(trim((string) ($fields['generic_name'] ?? '')));
        $sku = Str::lower(trim((string) ($fields['sku'] ?? '')));
        $barcode = Str::lower(trim((string) ($fields['barcode'] ?? '')));
        $strength = Str::lower(trim((string) ($fields['strength'] ?? '')));
        $manufacturer = Str::lower(trim((string) ($fields['manufacturer_name'] ?? '')));

        if ($barcode !== '' && $barcode === $q) {
            return 1000;
        }
        if ($sku !== '' && $sku === $q) {
            return 980;
        }
        if ($name !== '' && $name === $q) {
            return 950;
        }

        // Brand token prefix: "Napa 500mg", "Napa-DS"
        if ($name !== '' && (str_starts_with($name, $q.' ') || str_starts_with($name, $q.'-'))) {
            return 900;
        }
        if ($name !== '' && str_starts_with($name, $q)) {
            $next = $name[strlen($q)] ?? '';
            // Whole brand match vs continuing letters (napadol)
            return ($next === '' || ! ctype_alpha($next)) ? 900 : 750;
        }

        if ($sku !== '' && str_starts_with($sku, $q)) {
            return 700;
        }

        if ($name !== '' && str_contains($name, $q)) {
            return 650;
        }

        if ($generic !== '' && (str_starts_with($generic, $q.' ') || str_starts_with($generic, $q))) {
            $next = $generic[strlen($q)] ?? '';
            return ($next === '' || ! ctype_alpha($next) || str_starts_with($generic, $q.' ')) ? 450 : 380;
        }

        if ($generic !== '' && str_contains($generic, $q)) {
            return 350;
        }

        if ($strength !== '' && str_contains($strength, $q)) {
            return 250;
        }

        if ($manufacturer !== '' && str_contains($manufacturer, $q)) {
            return 150;
        }

        if ($sku !== '' && str_contains($sku, $q)) {
            return 120;
        }

        return 50;
    }

    /**
     * SQLite/MySQL-friendly relevance ordering for paginated list queries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Query\Builder  $query
     */
    public static function orderQuery(mixed $query, string $term): void
    {
        $q = trim($term);
        if ($q === '') {
            $query->orderBy('name');

            return;
        }

        $like = '%'.$q.'%';
        $prefix = $q.'%';
        $prefixSpace = $q.' %';
        $prefixDash = $q.'-%';

        $query->orderByRaw(
            'CASE
                WHEN lower(coalesce(barcode, \'\')) = lower(?) THEN 1000
                WHEN lower(coalesce(sku, \'\')) = lower(?) THEN 980
                WHEN lower(coalesce(name, \'\')) = lower(?) THEN 950
                WHEN lower(coalesce(name, \'\')) LIKE lower(?) THEN 900
                WHEN lower(coalesce(name, \'\')) LIKE lower(?) THEN 900
                WHEN lower(coalesce(name, \'\')) LIKE lower(?) THEN 750
                WHEN lower(coalesce(sku, \'\')) LIKE lower(?) THEN 700
                WHEN lower(coalesce(name, \'\')) LIKE lower(?) THEN 650
                WHEN lower(coalesce(generic_name, \'\')) LIKE lower(?) THEN 450
                WHEN lower(coalesce(generic_name, \'\')) LIKE lower(?) THEN 350
                ELSE 100
            END DESC',
            [$q, $q, $q, $prefixSpace, $prefixDash, $prefix, $prefix, $like, $prefix, $like]
        )->orderBy('name');
    }

    /**
     * @param  Collection<int, object>  $items
     * @return Collection<int, object>
     */
    public static function sort(Collection $items, string $term): Collection
    {
        $term = trim($term);
        if ($term === '' || $items->isEmpty()) {
            return $items->values();
        }

        return $items
            ->sort(function ($a, $b) use ($term) {
                $scoreA = self::score($term, self::fieldsFrom($a));
                $scoreB = self::score($term, self::fieldsFrom($b));
                if ($scoreA !== $scoreB) {
                    return $scoreB <=> $scoreA;
                }

                $nameA = Str::lower((string) (data_get($a, 'name') ?? ''));
                $nameB = Str::lower((string) (data_get($b, 'name') ?? ''));

                return $nameA <=> $nameB;
            })
            ->values();
    }

    /**
     * @return array{
     *     name?:string|null,
     *     generic_name?:string|null,
     *     sku?:string|null,
     *     barcode?:string|null,
     *     strength?:string|null,
     *     manufacturer_name?:string|null
     * }
     */
    private static function fieldsFrom(object $item): array
    {
        return [
            'name' => data_get($item, 'name'),
            'generic_name' => data_get($item, 'generic_name'),
            'sku' => data_get($item, 'sku'),
            'barcode' => data_get($item, 'barcode'),
            'strength' => data_get($item, 'strength'),
            'manufacturer_name' => data_get($item, 'manufacturer_name')
                ?? data_get($item, 'manufacturer.name'),
        ];
    }
}
