<?php

use App\Http\Controllers\Api\Catalog\ProductSearchController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Central\PlatformAdminController;
use App\Http\Controllers\Central\PlatformAuditController;
use App\Http\Controllers\Central\PlatformAnnouncementController;
use App\Http\Controllers\Central\PlatformBillingController;
use App\Http\Controllers\Central\PlatformCatalogTemplateController;
use App\Http\Controllers\Central\PlatformResellerController;
use App\Http\Controllers\Central\PlatformDashboardController;
use App\Http\Controllers\Central\PlatformHealthController;
use App\Http\Controllers\Central\PlatformPlanController;
use App\Http\Controllers\Central\PlatformSettingsController;
use App\Http\Controllers\Central\PlatformTenantComplianceController;
use App\Http\Controllers\Central\PlatformTenantController;
use App\Http\Controllers\Central\PlatformTenantImpersonationController;
use App\Http\Controllers\Tenant\BarcodePrintController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\EmployeeController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\LedgerAccountController;
use App\Http\Controllers\Tenant\LedgerEntryController;
use App\Http\Controllers\Tenant\PackageSaleController;
use App\Http\Controllers\Tenant\PosController;
use App\Http\Controllers\Tenant\CategoryController;
use App\Http\Controllers\Tenant\StorageLocationController;
use App\Http\Controllers\Tenant\ManufacturerController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\ProductImportController;
use App\Http\Controllers\Tenant\ProductTypeController;
use App\Http\Controllers\Tenant\PromotionsController;
use App\Http\Controllers\Tenant\PurchaseController;
use App\Http\Controllers\Tenant\ReportController;
use App\Http\Controllers\Tenant\ReportsHubController;
use App\Http\Controllers\Tenant\SaleController;
use App\Http\Controllers\Tenant\SaleReturnController;
use App\Http\Controllers\Tenant\SmsController;
use App\Http\Controllers\Tenant\StockTransferController;
use App\Http\Controllers\Tenant\SupplierBillsController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\SupportController;
use App\Http\Controllers\Tenant\TeamUserController;
use App\Http\Controllers\Tenant\TenantSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
});

Route::middleware(['auth', 'verified', 'tenant.subscription'])->group(function () {
    Route::middleware(['tenant.staff'])->group(function () {
        Route::name('tenant.')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
            Route::get('/sales/package', [PackageSaleController::class, 'index'])->name('sales.package');
            Route::get('/sales/returns', [SaleReturnController::class, 'index'])->name('sales.returns.index');
            Route::get('/sales/returns/create', [SaleReturnController::class, 'create'])->name('sales.returns.create');
            Route::post('/sales/returns', [SaleReturnController::class, 'store'])->name('sales.returns.store');

            Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
            Route::post('/pos/sales', [PosController::class, 'store'])->name('pos.sales.store');

            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::get('/purchases/supplier-bills', [SupplierBillsController::class, 'index'])->name('purchases.supplier-bills');

            Route::get('/accounts', [LedgerAccountController::class, 'index'])->name('accounts.index');
            Route::get('/accounts/create', [LedgerAccountController::class, 'create'])->name('accounts.create');
            Route::post('/accounts', [LedgerAccountController::class, 'store'])->name('accounts.store');
            Route::get('/accounts/{ledger_account}', [LedgerAccountController::class, 'show'])->name('accounts.show');
            Route::post('/accounts/{ledger_account}/entries', [LedgerEntryController::class, 'store'])->name('accounts.entries.store');

            Route::resource('products', ProductController::class);
            Route::resource('categories', CategoryController::class)->except(['show']);
            Route::resource('product-types', ProductTypeController::class)->except(['show']);
            Route::resource('manufacturers', ManufacturerController::class)->except(['show']);
            Route::resource('storage-locations', StorageLocationController::class)->except(['show']);
            Route::get('/catalog/import', [ProductImportController::class, 'index'])->name('catalog.import.index');
            Route::get('/catalog/import/sample', [ProductImportController::class, 'sample'])->name('catalog.import.sample');
            Route::post('/catalog/import/preview', [ProductImportController::class, 'preview'])->name('catalog.import.preview');
            Route::post('/catalog/import', [ProductImportController::class, 'store'])->name('catalog.import.store');

            Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');

            Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
            Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
            Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');

            Route::resource('suppliers', SupplierController::class)->except(['show']);

            Route::resource('customers', CustomerController::class)->except(['show']);

            Route::get('/reports', [ReportsHubController::class, 'index'])->name('reports.hub');
            Route::get('/reports/summary', [ReportController::class, 'summary'])->name('reports.summary');
            Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

            Route::get('/settings', [TenantSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [TenantSettingsController::class, 'update'])->name('settings.update');

            Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');

            Route::get('/team/users', [TeamUserController::class, 'index'])->name('team.users.index');
            Route::get('/team/users/create', [TeamUserController::class, 'create'])->name('team.users.create');
            Route::post('/team/users', [TeamUserController::class, 'store'])->name('team.users.store');
            Route::get('/team/users/{id}/edit', [TeamUserController::class, 'edit'])->name('team.users.edit');
            Route::put('/team/users/{id}', [TeamUserController::class, 'update'])->name('team.users.update');
            Route::delete('/team/users/{id}', [TeamUserController::class, 'destroy'])->name('team.users.destroy');

            Route::get('/support', [SupportController::class, 'index'])->name('support.index');

            Route::get('/global-settings', function () {
                if (auth()->user()?->shouldUsePlatformDashboard()) {
                    return redirect()->route('platform.dashboard');
                }

                return redirect()->route('tenant.settings.edit')->with('info', __('Global settings are available to platform operators.'));
            })->name('global-settings');

            Route::resource('promotions', PromotionsController::class)->except(['show']);

            Route::resource('employees', EmployeeController::class)->except(['show']);

            Route::get('/catalog/product-search', ProductSearchController::class)->name('catalog.product-search');

            Route::get('/barcodes/{product}', [BarcodePrintController::class, 'show'])->name('barcodes.show');
        });
    });

    Route::middleware(['role:super admin'])->prefix('platform')->name('platform.')->group(function () {
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::get('/health', [PlatformHealthController::class, 'index'])->name('health.index');
        Route::get('/tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [PlatformTenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [PlatformTenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}', [PlatformTenantController::class, 'show'])->name('tenants.show');
        Route::get('/tenants/{tenant}/edit', [PlatformTenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [PlatformTenantController::class, 'update'])->name('tenants.update');
        Route::post('/tenants/{tenant}/owner', [PlatformTenantController::class, 'storeOwner'])->name('tenants.owner.store');
        Route::post('/tenants/{tenant}/owner/resend-invite', [PlatformTenantController::class, 'resendOwnerInvite'])
            ->name('tenants.owner.resend-invite');
        Route::post('/tenants/{tenant}/suspend', [PlatformTenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/unsuspend', [PlatformTenantController::class, 'unsuspend'])->name('tenants.unsuspend');
        Route::get('/tenants/{tenant}/export', [PlatformTenantComplianceController::class, 'export'])
            ->name('tenants.export');
        Route::post('/tenants/{tenant}/purge', [PlatformTenantComplianceController::class, 'purge'])
            ->name('tenants.purge');
        Route::post('/tenants/{tenant}/impersonate', [PlatformTenantImpersonationController::class, 'store'])
            ->name('tenants.impersonate');
        Route::post('/impersonation/stop', [PlatformTenantImpersonationController::class, 'destroy'])
            ->name('impersonation.destroy');
        Route::resource('plans', PlatformPlanController::class)->except(['show']);
        Route::get('/admins', [PlatformAdminController::class, 'index'])->name('admins.index');
        Route::get('/audit', [PlatformAuditController::class, 'index'])->name('audit.index');
        Route::get('/billing', [PlatformBillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/invoices', [PlatformBillingController::class, 'store'])->name('billing.invoices.store');
        Route::put('/billing/invoices/{invoice}', [PlatformBillingController::class, 'update'])->name('billing.invoices.update');
        Route::delete('/billing/invoices/{invoice}', [PlatformBillingController::class, 'destroy'])->name('billing.invoices.destroy');
        Route::get('/billing/invoices/{invoice}/preview', [PlatformBillingController::class, 'printPreview'])->name('billing.invoices.preview');
        Route::get('/billing/invoices/{invoice}/pdf', [PlatformBillingController::class, 'download'])->name('billing.invoices.pdf');
        Route::post('/billing/invoices/{invoice}/paid', [PlatformBillingController::class, 'markPaid'])->name('billing.invoices.paid');
        Route::post('/billing/invoices/{invoice}/failed', [PlatformBillingController::class, 'markFailed'])->name('billing.invoices.failed');
        Route::get('/settings', [PlatformSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
        Route::resource('resellers', PlatformResellerController::class)->except(['show']);
        Route::resource('catalog-templates', PlatformCatalogTemplateController::class);
        Route::post('catalog-templates/{catalog_template}/items', [PlatformCatalogTemplateController::class, 'storeItem'])
            ->name('catalog-templates.items.store');
        Route::delete('catalog-templates/{catalog_template}/items/{item}', [PlatformCatalogTemplateController::class, 'destroyItem'])
            ->name('catalog-templates.items.destroy');
        Route::post('catalog-templates/{catalog_template}/apply', [PlatformCatalogTemplateController::class, 'apply'])
            ->name('catalog-templates.apply');
        Route::resource('announcements', PlatformAnnouncementController::class)->except(['show']);
    });
});
