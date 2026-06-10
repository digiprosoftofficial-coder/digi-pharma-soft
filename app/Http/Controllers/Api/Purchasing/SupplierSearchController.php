<?php

namespace App\Http\Controllers\Api\Purchasing;

use App\Domain\Purchasing\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('purchases.manage'), 403);

        $term = trim((string) $request->input('q'));

        if (strlen($term) < 1) {
            return response()->json(['data' => []]);
        }

        $suppliers = Supplier::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone', 'email', 'balance_due']);

        return response()->json(['data' => $suppliers]);
    }
}
