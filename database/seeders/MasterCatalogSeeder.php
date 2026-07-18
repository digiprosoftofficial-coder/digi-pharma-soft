<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\MasterProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter set of common medicines available in Bangladesh pharmacies.
 * This is a small seed sample; the full ~20-25k master catalog is intended
 * to be imported/maintained centrally on top of this structure.
 */
class MasterCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->medicines() as $row) {
            $sku = $row['sku'] ?? 'MSTR-'.Str::upper(Str::slug($row['name'], ''));

            MasterProduct::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $row['name'],
                    'generic_name' => $row['generic_name'] ?? null,
                    'strength' => $row['strength'] ?? null,
                    'manufacturer_name' => $row['manufacturer_name'] ?? null,
                    'product_type' => $row['product_type'] ?? 'other',
                    'drug_class' => $row['drug_class'] ?? null,
                    'base_unit' => $row['base_unit'] ?? 'strip',
                    'pieces_per_strip' => $row['pieces_per_strip'] ?? null,
                    'strips_per_box' => $row['strips_per_box'] ?? null,
                    'barcode' => $row['barcode'] ?? null,
                    'mrp' => $row['mrp'] ?? 0,
                    'default_purchase_price' => $row['default_purchase_price'] ?? round(($row['mrp'] ?? 0) * 0.85, 2),
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function medicines(): array
    {
        return [
            ['name' => 'Napa 500mg', 'generic_name' => 'Paracetamol', 'strength' => '500 mg', 'manufacturer_name' => 'Beximco Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Analgesic & antipyretic', 'pieces_per_strip' => 10, 'mrp' => 12],
            ['name' => 'Napa Extra', 'generic_name' => 'Paracetamol + Caffeine', 'strength' => '500 mg + 65 mg', 'manufacturer_name' => 'Beximco Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Analgesic & antipyretic', 'pieces_per_strip' => 10, 'mrp' => 15],
            ['name' => 'Ace 500mg', 'generic_name' => 'Paracetamol', 'strength' => '500 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Analgesic & antipyretic', 'pieces_per_strip' => 10, 'mrp' => 12],
            ['name' => 'Ace Plus', 'generic_name' => 'Paracetamol + Caffeine', 'strength' => '500 mg + 65 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Analgesic & antipyretic', 'pieces_per_strip' => 10, 'mrp' => 15],
            ['name' => 'Fast 500mg', 'generic_name' => 'Paracetamol', 'strength' => '500 mg', 'manufacturer_name' => 'ACME Laboratories', 'product_type' => 'tablet', 'drug_class' => 'Analgesic & antipyretic', 'pieces_per_strip' => 10, 'mrp' => 12],

            ['name' => 'Seclo 20mg', 'generic_name' => 'Omeprazole', 'strength' => '20 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'capsule', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 10, 'mrp' => 70],
            ['name' => 'Losectil 20mg', 'generic_name' => 'Omeprazole', 'strength' => '20 mg', 'manufacturer_name' => 'Eskayef Pharmaceuticals', 'product_type' => 'capsule', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 10, 'mrp' => 70],
            ['name' => 'Maxpro 20mg', 'generic_name' => 'Esomeprazole', 'strength' => '20 mg', 'manufacturer_name' => 'Renata Limited', 'product_type' => 'tablet', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 14, 'mrp' => 98],
            ['name' => 'Sergel 20mg', 'generic_name' => 'Esomeprazole', 'strength' => '20 mg', 'manufacturer_name' => 'Healthcare Pharmaceuticals', 'product_type' => 'capsule', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 14, 'mrp' => 98],
            ['name' => 'Pantonix 20mg', 'generic_name' => 'Pantoprazole', 'strength' => '20 mg', 'manufacturer_name' => 'Incepta Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 10, 'mrp' => 70],

            ['name' => 'Monas 10mg', 'generic_name' => 'Montelukast', 'strength' => '10 mg', 'manufacturer_name' => 'Acme Laboratories', 'product_type' => 'tablet', 'drug_class' => 'Respiratory system drugs', 'pieces_per_strip' => 10, 'mrp' => 90],
            ['name' => 'Montene 10mg', 'generic_name' => 'Montelukast', 'strength' => '10 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Respiratory system drugs', 'pieces_per_strip' => 10, 'mrp' => 90],
            ['name' => 'Fexo 120mg', 'generic_name' => 'Fexofenadine', 'strength' => '120 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Allergy & immune system', 'pieces_per_strip' => 10, 'mrp' => 90],
            ['name' => 'Alatrol 10mg', 'generic_name' => 'Cetirizine', 'strength' => '10 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Allergy & immune system', 'pieces_per_strip' => 10, 'mrp' => 30],
            ['name' => 'Fenadin 120mg', 'generic_name' => 'Fexofenadine', 'strength' => '120 mg', 'manufacturer_name' => 'ACME Laboratories', 'product_type' => 'tablet', 'drug_class' => 'Allergy & immune system', 'pieces_per_strip' => 10, 'mrp' => 85],

            ['name' => 'Amodis 400mg', 'generic_name' => 'Metronidazole', 'strength' => '400 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Antimicrobial drugs', 'pieces_per_strip' => 10, 'mrp' => 40],
            ['name' => 'Filmet 400mg', 'generic_name' => 'Metronidazole', 'strength' => '400 mg', 'manufacturer_name' => 'Beximco Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Antimicrobial drugs', 'pieces_per_strip' => 10, 'mrp' => 40],
            ['name' => 'Azithromycin 500mg (Azin)', 'generic_name' => 'Azithromycin', 'strength' => '500 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Antimicrobial drugs', 'pieces_per_strip' => 3, 'mrp' => 90],
            ['name' => 'Cef-3 200mg', 'generic_name' => 'Cefixime', 'strength' => '200 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'capsule', 'drug_class' => 'Antimicrobial drugs', 'pieces_per_strip' => 10, 'mrp' => 350],
            ['name' => 'Fimoxyclav 625mg', 'generic_name' => 'Amoxicillin + Clavulanic Acid', 'strength' => '500 mg + 125 mg', 'manufacturer_name' => 'Aristopharma', 'product_type' => 'tablet', 'drug_class' => 'Antimicrobial drugs', 'pieces_per_strip' => 6, 'mrp' => 300],
            ['name' => 'Moxacil 500mg', 'generic_name' => 'Amoxicillin', 'strength' => '500 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'capsule', 'drug_class' => 'Antimicrobial drugs', 'pieces_per_strip' => 10, 'mrp' => 80],

            ['name' => 'Maxsand 50mg', 'generic_name' => 'Losartan Potassium', 'strength' => '50 mg', 'manufacturer_name' => 'Incepta Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Cardiovascular system drugs', 'pieces_per_strip' => 10, 'mrp' => 80],
            ['name' => 'Angilock 50mg', 'generic_name' => 'Losartan Potassium', 'strength' => '50 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Cardiovascular system drugs', 'pieces_per_strip' => 10, 'mrp' => 80],
            ['name' => 'Amdocal 5mg', 'generic_name' => 'Amlodipine', 'strength' => '5 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Cardiovascular system drugs', 'pieces_per_strip' => 10, 'mrp' => 45],
            ['name' => 'Bisocor 5mg', 'generic_name' => 'Bisoprolol', 'strength' => '5 mg', 'manufacturer_name' => 'Incepta Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Cardiovascular system drugs', 'pieces_per_strip' => 10, 'mrp' => 70],
            ['name' => 'Atova 10mg', 'generic_name' => 'Atorvastatin', 'strength' => '10 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Cardiovascular system drugs', 'pieces_per_strip' => 10, 'mrp' => 80],

            ['name' => 'Comet 500mg', 'generic_name' => 'Metformin', 'strength' => '500 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Endocrine & metabolic system drugs', 'pieces_per_strip' => 10, 'mrp' => 40],
            ['name' => 'Diamicron MR 30mg', 'generic_name' => 'Gliclazide', 'strength' => '30 mg', 'manufacturer_name' => 'Servier', 'product_type' => 'tablet', 'drug_class' => 'Endocrine & metabolic system drugs', 'pieces_per_strip' => 10, 'mrp' => 110],

            ['name' => 'Tufnil 200mg', 'generic_name' => 'Tolfenamic Acid', 'strength' => '200 mg', 'manufacturer_name' => 'Beximco Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Analgesic & antipyretic', 'pieces_per_strip' => 10, 'mrp' => 80],
            ['name' => 'Etorix 90mg', 'generic_name' => 'Etoricoxib', 'strength' => '90 mg', 'manufacturer_name' => 'Incepta Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Musculoskeletal systems', 'pieces_per_strip' => 10, 'mrp' => 120],
            ['name' => 'Napa Syrup 60ml', 'generic_name' => 'Paracetamol', 'strength' => '120 mg/5 ml', 'manufacturer_name' => 'Beximco Pharmaceuticals', 'product_type' => 'syrup', 'drug_class' => 'Analgesic & antipyretic', 'base_unit' => 'piece', 'mrp' => 35],
            ['name' => 'Ambrox Syrup 100ml', 'generic_name' => 'Ambroxol', 'strength' => '15 mg/5 ml', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'syrup', 'drug_class' => 'Respiratory system drugs', 'base_unit' => 'piece', 'mrp' => 75],
            ['name' => 'Adovas Syrup 100ml', 'generic_name' => 'Vasaka + Others', 'strength' => '100 ml', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'syrup', 'drug_class' => 'Respiratory system drugs', 'base_unit' => 'piece', 'mrp' => 90],

            ['name' => 'Sinaflex', 'generic_name' => 'Vitamin B Complex', 'strength' => '-', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Vitamin, mineral & nutritional deficiency', 'pieces_per_strip' => 10, 'mrp' => 40],
            ['name' => 'Calbo-D', 'generic_name' => 'Calcium + Vitamin D3', 'strength' => '500 mg + 200 IU', 'manufacturer_name' => 'Radiant Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Bone formation & bone disorders', 'pieces_per_strip' => 10, 'mrp' => 90],
            ['name' => 'Ceevit', 'generic_name' => 'Vitamin C (Ascorbic Acid)', 'strength' => '250 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Vitamin, mineral & nutritional deficiency', 'pieces_per_strip' => 10, 'mrp' => 40],
            ['name' => 'Zox 20mg', 'generic_name' => 'Zinc', 'strength' => '20 mg', 'manufacturer_name' => 'ACME Laboratories', 'product_type' => 'syrup', 'drug_class' => 'Vitamin, mineral & nutritional deficiency', 'base_unit' => 'piece', 'mrp' => 55],

            ['name' => 'Reneta ORS', 'generic_name' => 'Oral Rehydration Salt', 'strength' => '-', 'manufacturer_name' => 'Renata Limited', 'product_type' => 'other', 'drug_class' => 'Gastrointestinal system drugs', 'base_unit' => 'piece', 'mrp' => 8],
            ['name' => 'Orsaline-N', 'generic_name' => 'Oral Rehydration Salt', 'strength' => '-', 'manufacturer_name' => 'ACME Laboratories', 'product_type' => 'other', 'drug_class' => 'Gastrointestinal system drugs', 'base_unit' => 'piece', 'mrp' => 8],
            ['name' => 'Emistat 8mg', 'generic_name' => 'Ondansetron', 'strength' => '8 mg', 'manufacturer_name' => 'Healthcare Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 10, 'mrp' => 90],
            ['name' => 'Motigut 10mg', 'generic_name' => 'Domperidone', 'strength' => '10 mg', 'manufacturer_name' => 'Square Pharmaceuticals', 'product_type' => 'tablet', 'drug_class' => 'Gastrointestinal system drugs', 'pieces_per_strip' => 10, 'mrp' => 35],
        ];
    }
}
