<?php

namespace App\Support;

/**
 * Business rules and open decisions for pharmacy SaaS (stakeholder review).
 *
 * **Returns**
 * - Each return line targets a `product_batch_id` and increases `quantity_on_hand` by the returned quantity.
 * - Optional `sale_id` links the return to an original sale for audit; walk-in returns omit it.
 * - Refund per line is `quantity * unit_price`; total refund is the sum of lines (cash-out is not modeled separately).
 * - Partial returns and mixed batches on one return document are allowed.
 *
 * **Package sell**
 * - Uses the same POS checkout pipeline as single-item sales (`SaleService::checkout`).
 * - UI may label the flow “package”; pricing is still per batch line (bundle presets are a future enhancement).
 *
 * **Discounts & coupons**
 * - Flat cart discount and tax are passed from POS.
 * - `DiscountCoupon` applies an additional percent off the line subtotal before tax; see `SaleService`.
 * - Fixed-amount coupons, stackable coupons, and category-scoped discounts are not implemented yet.
 *
 * **Accounting**
 * - `ledger_entries` are single-sided cashbook-style lines (debit/credit to one account).
 * - Double-entry balancing and automatic posting from sales/purchases are future work; see `LedgerEntryController`.
 *
 * **SMS**
 * - No provider integration in MVP; queue + templates TBD when a vendor is chosen.
 *
 * **Tenant users**
 * - Create user with password + single role; email verification flow not wired in this module.
 */
final class BusinessDefaults
{
    public const RETURN_STOCK_INCREASES_BATCH = true;

    public const PACKAGE_SALE_USES_POS_CHECKOUT = true;
}
