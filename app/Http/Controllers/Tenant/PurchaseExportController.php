<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Purchasing\Models\Purchase;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PurchaseExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        $from = $request->date('date_from') ?? now()->subDays(30)->toDateString();
        $to = $request->date('date_to') ?? now()->toDateString();
        $supplierId = $request->integer('supplier_id');
        $term = trim((string) $request->input('q', ''));

        $query = Purchase::query()
            ->with('supplier')
            ->whereDate('purchased_at', '>=', $from)
            ->whereDate('purchased_at', '<=', $to)
            ->orderBy('purchased_at');

        if ($supplierId > 0) {
            $query->where('supplier_id', $supplierId);
        }

        if ($term !== '') {
            $query->where(function ($w) use ($term) {
                $w->where('invoice_no', 'like', '%'.$term.'%')
                    ->orWhereHas('lines.product', function ($productQuery) use ($term) {
                        $productQuery->where('name', 'like', '%'.$term.'%')
                            ->orWhere('sku', 'like', '%'.$term.'%');
                    });
            });
        }

        $fileName = 'purchases-'.$from.'_to_'.$to.'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['invoice_no', 'supplier', 'purchased_at', 'subtotal', 'tax', 'discount', 'total', 'paid', 'due', 'status']);
            $query->cursor()->each(function (Purchase $purchase) use ($out) {
                fputcsv($out, [
                    $purchase->invoice_no,
                    $purchase->supplier?->name,
                    $purchase->purchased_at?->toDateString(),
                    $purchase->subtotal,
                    $purchase->tax,
                    $purchase->discount,
                    $purchase->total,
                    $purchase->paid,
                    $purchase->due,
                    $purchase->status,
                ]);
            });
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
