<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Repositories\ProductRepository;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductSearchController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403);

        $request->validate(['q' => ['required', 'string', 'min:1', 'max:100']]);

        $items = $this->products->searchByTerm($request->string('q')->toString(), 30);

        return response()->json([
            'data' => ProductResource::collection($items)->resolve(),
        ]);
    }
}
