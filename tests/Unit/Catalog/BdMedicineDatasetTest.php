<?php

namespace Tests\Unit\Catalog;

use App\Support\Catalog\BdMedicineDataset;
use PHPUnit\Framework\TestCase;

class BdMedicineDatasetTest extends TestCase
{
    public function test_maps_kaggle_row_to_master_columns(): void
    {
        $raw = BdMedicineDataset::toMasterRaw(
            [
                'brand_id' => '10452',
                'brand_name' => 'Napa',
                'dosage_form' => 'Tablet',
                'generic' => 'Paracetamol',
                'strength' => '500 mg',
                'manufacturer' => 'Beximco Pharmaceuticals Ltd.',
                'package_container' => "Unit Price: ৳ 1.20,(10's pack: ৳ 12.00),",
            ],
            ['paracetamol' => 'Analgesic & antipyretic'],
        );

        $this->assertNotNull($raw);
        $this->assertSame('Napa 500 mg', $raw['name']);
        $this->assertSame('Paracetamol', $raw['generic_name']);
        $this->assertSame('tablet', $raw['product_type']);
        $this->assertSame('strip', $raw['base_unit']);
        $this->assertSame('MSTR-BD-10452', $raw['sku']);
        $this->assertSame('1.2', $raw['mrp']);
        $this->assertSame('Analgesic & antipyretic', $raw['drug_class']);
        $this->assertSame('', $raw['default_purchase_price']);
    }

    public function test_product_type_and_unit_mapping(): void
    {
        $this->assertSame('capsule', BdMedicineDataset::productType('Inhalation Capsule'));
        $this->assertSame('drops', BdMedicineDataset::productType('Pediatric Drops'));
        $this->assertSame('drops', BdMedicineDataset::productType('Ophthalmic Solution'));
        $this->assertSame('injection', BdMedicineDataset::productType('IM/IV Injection'));
        $this->assertSame('syrup', BdMedicineDataset::productType('Powder for Suspension'));
        $this->assertSame('cream', BdMedicineDataset::productType('Ointment'));
        $this->assertSame('pack', BdMedicineDataset::productType('Inhaler'));
        $this->assertSame('piece', BdMedicineDataset::baseUnit('syrup'));
        $this->assertSame('piece', BdMedicineDataset::baseUnit('injection'));
    }

    public function test_skips_rows_without_a_brand_name(): void
    {
        $this->assertNull(BdMedicineDataset::toMasterRaw([
            'brand_id' => '1',
            'brand_name' => '  ',
        ]));
    }
}
