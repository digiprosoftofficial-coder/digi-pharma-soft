<?php

namespace Tests\Unit\Catalog;

use App\Support\Catalog\CatalogSearchRelevance;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogSearchRelevanceTest extends TestCase
{
    #[Test]
    public function it_ranks_brand_name_prefix_above_generic_substring(): void
    {
        $term = 'napa';

        $napa = CatalogSearchRelevance::score($term, [
            'name' => 'Napa 500 mg',
            'generic_name' => 'Paracetamol',
        ]);
        $bexidal = CatalogSearchRelevance::score($term, [
            'name' => 'Bexidal 50 mg',
            'generic_name' => 'Mebhydrolin Napadisylate',
        ]);
        $lonapam = CatalogSearchRelevance::score($term, [
            'name' => 'Lonapam 1 mg',
            'generic_name' => 'Clonazepam',
        ]);

        $this->assertGreaterThan($lonapam, $napa);
        $this->assertGreaterThan($bexidal, $napa);
        $this->assertGreaterThan(0, $bexidal);
    }

    #[Test]
    public function it_sorts_collection_with_napa_first(): void
    {
        $items = Collection::make([
            (object) ['name' => 'Bexidal 50 mg', 'generic_name' => 'Mebhydrolin Napadisylate'],
            (object) ['name' => 'Lonapam 1 mg', 'generic_name' => 'Clonazepam'],
            (object) ['name' => 'Napa 500 mg', 'generic_name' => 'Paracetamol'],
            (object) ['name' => 'Dayhista 50 mg', 'generic_name' => 'Mebhydrolin Napadisylate'],
        ]);

        $sorted = CatalogSearchRelevance::sort($items, 'napa');

        $this->assertSame('Napa 500 mg', $sorted->first()->name);
    }
}
