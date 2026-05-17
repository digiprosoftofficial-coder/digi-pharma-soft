<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

final class BarcodePrintController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless(auth()->user()?->can('products.view'), 403);
        $this->authorize('view', $product);

        $raw = $product->barcode ?: (string) $product->sku;
        $generator = new BarcodeGeneratorPNG;

        [$code, $type] = $this->resolveBarcode($raw, $product);

        try {
            $png = $generator->getBarcode($code, $type);
        } catch (Throwable) {
            $fallback = $raw !== '' ? $raw : (string) $product->sku;
            $png = $generator->getBarcode($fallback, BarcodeGeneratorPNG::TYPE_CODE_128);
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="barcode-'.$product->getKey().'.png"',
        ]);
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function resolveBarcode(string $raw, Product $product): array
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if (strlen($digits) === 13 && ctype_digit($digits)) {
            return [$digits, BarcodeGeneratorPNG::TYPE_EAN_13];
        }

        if (strlen($digits) === 12 && ctype_digit($digits)) {
            return [$digits.$this->ean13CheckDigit($digits), BarcodeGeneratorPNG::TYPE_EAN_13];
        }

        return [$raw !== '' ? $raw : (string) $product->sku, BarcodeGeneratorPNG::TYPE_CODE_128];
    }

    private function ean13CheckDigit(string $twelve): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $n = (int) $twelve[$i];
            $sum += ($i % 2 === 0) ? $n : $n * 3;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
