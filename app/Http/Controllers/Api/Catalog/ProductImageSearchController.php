<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Repositories\ProductRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use App\Support\Catalog\GeminiMedicineRecognizer;
use App\Support\Purchasing\LastPurchasePriceLookup;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class ProductImageSearchController extends Controller
{
    public function __construct(
        private readonly GeminiMedicineRecognizer $recognizer,
        private readonly ProductRepository $products,
        private readonly LastPurchasePriceLookup $lastPurchase,
        private readonly TenantContext $tenants,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403);

        $tenant = $this->tenants->tenant();
        abort_unless(TenantFeatures::photoSearchEnabled($tenant), 403, __('sales.pos_photo_search_forbidden'));

        if (! $this->recognizer->configured()) {
            return response()->json([
                'message' => __('sales.pos_photo_search_not_configured'),
            ], 503);
        }

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ], [
            'image.required' => __('sales.pos_photo_search_invalid_image'),
            'image.image' => __('sales.pos_photo_search_invalid_image'),
            'image.mimes' => __('sales.pos_photo_search_invalid_image'),
            'image.max' => __('sales.pos_photo_search_invalid_image'),
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['image'];
        $bytes = file_get_contents($file->getRealPath() ?: $file->getPathname());

        if ($bytes === false || $bytes === '') {
            return response()->json([
                'message' => __('sales.pos_photo_search_invalid_image'),
            ], 422);
        }

        // Soft cap raw payload size after client compression (Gemini inline limits).
        if (strlen($bytes) > 4 * 1024 * 1024) {
            return response()->json([
                'message' => __('sales.pos_photo_search_invalid_image'),
            ], 422);
        }

        try {
            $recognition = $this->recognizer->recognize(
                $bytes,
                (string) ($file->getMimeType() ?: 'image/jpeg'),
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => __('sales.pos_photo_search_unavailable'),
                'detail' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 502);
        }

        $query = $recognition['query'];
        $barcode = $recognition['barcode'];

        $items = collect();
        if (filled($barcode)) {
            $items = $this->products->searchByTerm((string) $barcode, 10);
            // Prefer exact barcode hits; if the barcode search returned nothing useful, fall through.
            $exact = $items->first(fn ($p) => (string) ($p->barcode ?? '') === (string) $barcode);
            if ($exact) {
                $items = collect([$exact])->merge($items->reject(fn ($p) => $p->is($exact)))->values();
            } elseif ($items->isEmpty() && filled($query)) {
                $items = $this->products->searchByTerm((string) $query, 15);
            }
        } elseif (filled($query)) {
            $items = $this->products->searchByTerm((string) $query, 15);
        }

        $this->lastPurchase->attachToProducts($items);

        return response()->json([
            'recognition' => $recognition,
            'query' => $query,
            'data' => ProductResource::collection($items)->resolve(),
        ]);
    }
}
