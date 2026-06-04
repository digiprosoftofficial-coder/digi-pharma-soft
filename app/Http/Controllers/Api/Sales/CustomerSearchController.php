<?php

namespace App\Http\Controllers\Api\Sales;

use App\Domain\Sales\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('pos.access'), 403);

        $term = trim((string) $request->input('q'));

        if (strlen($term) < 1) {
            return response()->json(['data' => []]);
        }

        $customers = Customer::query()
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone', 'balance_due']);

        return response()->json(['data' => $customers]);
    }
}
