<?php

namespace Database\Seeders;

use App\Domain\Billing\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price_cents' => 199900,
                'monthly_price_cents' => 199900,
                'yearly_price_cents' => 1999000,
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 10,
                'features' => [
                    'pos' => true,
                    'reports' => true,
                    'wholesale_pricing' => false,
                    'markup_pricing' => false,
                    'bulk_import' => true,
                    'advanced_catalog' => false,
                    'multi_branch' => false,
                    'supplier_branch_ledger' => false,
                    'employee_management' => true,
                    'attendance' => false,
                    'hr_payroll' => false,
                    'barcode_camera_scan' => false,
                    'pharmacy_notes' => false,
                    'package_sales' => false,
                ],
                'limits' => [
                    'max_products' => 500,
                    'max_import_rows' => 500,
                    'max_branches' => 1,
                    'max_users' => 3,
                ],
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional',
                'price_cents' => 399900,
                'monthly_price_cents' => 399900,
                'yearly_price_cents' => 3999000,
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 20,
                'features' => [
                    'pos' => true,
                    'reports' => true,
                    'wholesale_pricing' => true,
                    'markup_pricing' => true,
                    'bulk_import' => true,
                    'advanced_catalog' => true,
                    'multi_branch' => true,
                    'supplier_branch_ledger' => true,
                    'employee_management' => true,
                    'attendance' => true,
                    'hr_payroll' => false,
                    'barcode_camera_scan' => true,
                    'pharmacy_notes' => true,
                    'package_sales' => true,
                ],
                'limits' => [
                    'max_products' => 5000,
                    'max_import_rows' => 5000,
                    'max_branches' => 5,
                    'max_users' => 15,
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price_cents' => 799900,
                'monthly_price_cents' => 799900,
                'yearly_price_cents' => 7999000,
                'trial_days' => 14,
                'is_active' => true,
                'sort_order' => 30,
                'features' => [
                    'pos' => true,
                    'reports' => true,
                    'wholesale_pricing' => true,
                    'markup_pricing' => true,
                    'bulk_import' => true,
                    'advanced_catalog' => true,
                    'multi_branch' => true,
                    'supplier_branch_ledger' => true,
                    'employee_management' => true,
                    'attendance' => true,
                    'hr_payroll' => true,
                    'barcode_camera_scan' => true,
                    'pharmacy_notes' => true,
                    'package_sales' => true,
                ],
                'limits' => [
                    'max_products' => null,
                    'max_import_rows' => 50000,
                    'max_branches' => 50,
                    'max_users' => null,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
