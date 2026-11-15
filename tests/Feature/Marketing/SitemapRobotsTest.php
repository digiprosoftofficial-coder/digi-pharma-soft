<?php

namespace Tests\Feature\Marketing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapRobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_core_marketing_urls(): void
    {
        config(['app.url' => 'https://softopharma.digiprosoft.com']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('https://softopharma.digiprosoft.com/', false);
        $response->assertSee('https://softopharma.digiprosoft.com/pricing', false);
        $response->assertSee('https://softopharma.digiprosoft.com/demo', false);
        $response->assertSee('https://softopharma.digiprosoft.com/get-started', false);
        $response->assertSee('<lastmod>', false);
    }

    public function test_robots_allows_marketing_and_points_at_sitemap(): void
    {
        config(['app.url' => 'https://softopharma.digiprosoft.com']);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Allow: /pricing', false);
        $response->assertSee('Disallow: /platform/', false);
        $response->assertSee('Disallow: /dashboard', false);
        $response->assertSee('Sitemap: https://softopharma.digiprosoft.com/sitemap.xml', false);
    }

    public function test_llms_txt_describes_softopharma(): void
    {
        config(['app.url' => 'https://softopharma.digiprosoft.com']);

        $response = $this->get('/llms.txt');

        $response->assertOk();
        $response->assertSee('SoftoPharma', false);
        $response->assertSee('pharmacy management software', false);
        $response->assertSee('https://softopharma.digiprosoft.com/pricing', false);
    }
}
