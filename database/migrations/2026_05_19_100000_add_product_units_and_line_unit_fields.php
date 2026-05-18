<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type', 32)->default('other')->after('barcode');
            $table->string('base_unit', 32)->default('strip')->after('product_type');
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sell_unit', 32);
            $table->decimal('conversion_factor', 14, 4)->default(1);
            $table->decimal('purchase_price', 14, 4)->default(0);
            $table->decimal('sale_price', 14, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'sell_unit']);
        });

        Schema::table('sale_lines', function (Blueprint $table) {
            $table->string('sell_unit', 32)->nullable()->after('quantity');
            $table->decimal('conversion_factor', 14, 4)->nullable()->after('sell_unit');
            $table->decimal('quantity_base', 14, 4)->nullable()->after('conversion_factor');
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->string('sell_unit', 32)->nullable()->after('quantity');
            $table->decimal('conversion_factor', 14, 4)->nullable()->after('sell_unit');
            $table->decimal('quantity_base', 14, 4)->nullable()->after('conversion_factor');
        });

        Schema::table('catalog_template_items', function (Blueprint $table) {
            $table->string('product_type', 32)->default('other')->after('unit');
            $table->string('base_unit', 32)->default('strip')->after('product_type');
        });

        Schema::create('catalog_template_item_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_template_item_id')->constrained()->cascadeOnDelete();
            $table->string('sell_unit', 32);
            $table->decimal('conversion_factor', 14, 4)->default(1);
            $table->decimal('purchase_price', 14, 4)->default(0);
            $table->decimal('sale_price', 14, 4)->default(0);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_template_item_id', 'sell_unit'], 'catalog_template_item_units_unique');
        });

        $this->backfillExistingProducts();
        $this->backfillExistingLines();
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_template_item_units');

        Schema::table('catalog_template_items', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'base_unit']);
        });

        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn(['sell_unit', 'conversion_factor', 'quantity_base']);
        });

        Schema::table('sale_lines', function (Blueprint $table) {
            $table->dropColumn(['sell_unit', 'conversion_factor', 'quantity_base']);
        });

        Schema::dropIfExists('product_units');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'base_unit']);
        });
    }

    private function backfillExistingProducts(): void
    {
        $products = DB::table('products')->orderBy('id')->get();

        foreach ($products as $product) {
            $legacyUnit = strtolower((string) ($product->unit ?? 'pcs'));
            $baseUnit = match ($legacyUnit) {
                'strip', 'strips' => 'strip',
                'box', 'boxes' => 'box',
                'piece', 'pieces', 'pcs', 'pc' => 'piece',
                default => in_array($legacyUnit, ['strip', 'box', 'piece'], true) ? $legacyUnit : 'strip',
            };

            DB::table('products')->where('id', $product->id)->update([
                'product_type' => 'other',
                'base_unit' => $baseUnit,
            ]);

            DB::table('product_units')->insert([
                'product_id' => $product->id,
                'sell_unit' => $baseUnit,
                'conversion_factor' => 1,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillExistingLines(): void
    {
        $products = DB::table('products')->pluck('base_unit', 'id');

        foreach (DB::table('sale_lines')->orderBy('id')->get() as $line) {
            $baseUnit = $products[$line->product_id] ?? 'strip';
            DB::table('sale_lines')->where('id', $line->id)->update([
                'sell_unit' => $baseUnit,
                'conversion_factor' => 1,
                'quantity_base' => $line->quantity,
            ]);
        }

        foreach (DB::table('purchase_lines')->orderBy('id')->get() as $line) {
            $baseUnit = $products[$line->product_id] ?? 'strip';
            DB::table('purchase_lines')->where('id', $line->id)->update([
                'sell_unit' => $baseUnit,
                'conversion_factor' => 1,
                'quantity_base' => $line->quantity,
            ]);
        }
    }
};
