<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Support\Purchasing\LastPurchasePriceLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductLastPurchaseController extends Controller
{
    public function __construct(private readonly LastPurchasePriceLookup $lastPurchase) {}

    public function __invoke(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->can('products.view'), 403);
        abort_unless((int) $request->user()?->tenant_id === (int) $product->tenant_id, 403);

        $sellUnit = $request->string('sell_unit')->trim()->toString();
        $sellUnit = $sellUnit !== '' ? $sellUnit : null;

        return response()->json([
            'data' => $this->lastPurchase->latestForProduct($product->getKey(), $sellUnit),
        ]);
    }
}
