<?php

namespace Tests\Feature\Catalog;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductImageSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_503_when_gemini_is_not_configured(): void
    {
        $this->seed();
        config(['services.gemini.api_key' => null]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/catalog/product-image-search', [
                'image' => UploadedFile::fake()->image('pack.jpg', 640, 480),
            ])
            ->assertStatus(503)
            ->assertJsonFragment(['message' => __('sales.pos_photo_search_not_configured')]);
    }

    public function test_forbids_when_plan_feature_is_off(): void
    {
        $this->seed();
        config(['services.gemini.api_key' => 'test-key']);

        $plan = SubscriptionPlan::query()->where('slug', 'professional')->firstOrFail();
        $features = $plan->features ?? [];
        $features['photo_search'] = false;
        $plan->update(['features' => $features]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/catalog/product-image-search', [
                'image' => UploadedFile::fake()->image('pack.jpg', 640, 480),
            ])
            ->assertForbidden();
    }

    public function test_recognizes_medicine_and_returns_matching_products(): void
    {
        $this->seed();
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.0-flash',
            'services.gemini.endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
        ]);

        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->update([
            'name' => 'Napa',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'barcode' => '8901234567890',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'brand_name' => 'Napa',
                                'generic_name' => 'Paracetamol',
                                'strength' => '500 mg',
                                'barcode' => null,
                                'query' => 'Napa',
                                'confidence' => 0.91,
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->postJson('/catalog/product-image-search', [
                'image' => UploadedFile::fake()->image('napa.jpg', 800, 600),
            ])
            ->assertOk()
            ->assertJsonPath('query', 'Napa')
            ->assertJsonPath('recognition.brand_name', 'Napa')
            ->assertJsonPath('recognition.confidence', 0.91);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($product->getKey(), $ids);

        Http::assertSentCount(1);
    }

    public function test_prefers_exact_barcode_match_from_recognition(): void
    {
        $this->seed();
        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.model' => 'gemini-2.0-flash',
        ]);

        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $product->update(['barcode' => '8909999888777', 'name' => 'Seclo']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'brand_name' => 'Seclo',
                                'generic_name' => 'Omeprazole',
                                'strength' => '20 mg',
                                'barcode' => '8909999888777',
                                'query' => 'Seclo',
                                'confidence' => 0.88,
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/catalog/product-image-search', [
                'image' => UploadedFile::fake()->image('seclo.jpg', 640, 480),
            ])
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->getKey())
            ->assertJsonPath('recognition.barcode', '8909999888777');
    }

    public function test_rejects_non_image_uploads(): void
    {
        $this->seed();
        config(['services.gemini.api_key' => 'test-key']);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/catalog/product-image-search', [
                'image' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertStatus(422);
    }

    public function test_returns_502_when_gemini_http_fails(): void
    {
        $this->seed();
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
        ]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->postJson('/catalog/product-image-search', [
                'image' => UploadedFile::fake()->image('pack.jpg', 320, 240),
            ])
            ->assertStatus(502)
            ->assertJsonFragment(['message' => __('sales.pos_photo_search_unavailable')]);
    }
}
