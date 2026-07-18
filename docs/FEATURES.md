# Feature List — Pharmacy Management SaaS

A multi-tenant pharmacy management platform. **Platform operators** manage the SaaS network (tenants, plans, billing), and **tenant staff** run day-to-day pharmacy operations (POS, stock, purchasing, reports, HR).

_Last updated: 2026-07-18_

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Vue 3 + Inertia.js |
| Styling | Bootstrap 5 (SCSS) |
| Build | Vite 6 |
| Auth | Laravel Fortify + Sanctum (session + CSRF), 2FA columns |
| Permissions | spatie/laravel-permission (roles & permissions, team-scoped) |
| Audit | spatie/laravel-activitylog |
| PDF | barryvdh/laravel-dompdf (invoices) |
| Excel/CSV | maatwebsite/excel (imports & report exports) |
| Barcodes | picqer/php-barcode-generator + html5-qrcode (camera scan) |
| Offline | PWA (vite-plugin-pwa, service worker) + IndexedDB queue |
| Database | SQLite (dev) / MySQL (production) |

Architecture: domain-driven structure under `app/Domain/*` (Catalog, Sales, Purchasing, Inventory, Accounting, Hr, Billing, Tenant, Platform).

---

## 1. Platform / Super Admin Console (`/platform/*`)

Network-level management for SaaS operators.

- **Dashboard** — cross-tenant KPIs, revenue/adoption analytics, top tenants (30d), expiring-soon widget, system health widget
- **Tenant lifecycle** — 3-step onboarding wizard, edit, suspend/unsuspend (with reason), tenant detail page
- **Tenant owner management** — attach owner, email invite + resend invitation
- **Impersonation** — "View as pharmacy" with banner and audit trail
- **Subscription plans** — CRUD, per-plan feature flags and limits
- **Billing** — MRR, manual invoices (create/edit/delete), invoice PDF/preview, mark paid/failed, Stripe webhook stub, payment-delinquency auto-suspend
- **Compliance** — tenant data export (ZIP), permanent purge (suspended tenants), retention settings + daily command
- **System health** — queue status, failed jobs
- **Audit log** — platform-wide activity
- **Platform admins** — administrator list
- **Global settings** — trial defaults, support contact, SMS, feature flags, locale/timezone/country defaults
- **Resellers / distributors** — partner hierarchy, tenant assignment
- **Central drug catalog templates** — build templates and apply to a pharmacy
- **Network announcements** — banner shown across tenant apps
- **Platform product types** — shared product type definitions

---

## 2. Authentication & Access

- Login, registration, email verification, password reset/confirm (Fortify)
- Two-factor authentication columns (2FA support)
- Role-based access control with granular permissions (spatie), team/tenant scoped
- Trial & subscription enforcement middleware (auto-block after expiry)
- EN / BN locale switcher (per-user)

---

## 3. Point of Sale (POS) — `/pos`

- Fast product search (name, generic, strength, SKU, exact barcode)
- Barcode scanning via search box **and** live camera scanning (html5-qrcode)
- Quick-product tabs: Popular, Latest, Last sold
- Cart with per-line unit selection, quantity, price, discount
- FEFO batch allocation (first-expiry-first-out), auto stock deduction
- Cart-level discount (%) and coupon codes
- Multiple payment methods (cash, mobile, etc.), tendered/change calculation
- Credit/due sales tied to a customer
- On-the-fly new customer creation
- Invoice rounding (e.g. nearest 1 Taka)
- Print last invoice
- Mobile-responsive layout (card & table cart views)
- **Offline mode (PWA):** cached catalog search, offline sale queue in IndexedDB, auto-sync when back online, idempotent sync via `offline_client_id`, install-to-home-screen

---

## 4. Sales Management — `/sales`

- Sales list & invoice view
- Invoice printing (PDF)
- Void sale (with stock restoration)
- Sales payments (record payments against due invoices)
- Customer bills / dues tracking (per customer)
- **Sale returns** — create & list, stock restoration
- **Package sales** — sell predefined product bundles
- **Package templates** — CRUD of reusable package/bundle definitions

---

## 5. Purchasing — `/purchases`

- Purchase entry (create), list, detail view
- Purchase invoice printing
- Supplier search
- Supplier bills & dues tracking
- Purchase payments
- **Purchase returns** — create & list
- CSV export of purchases
- Void purchase

---

## 6. Catalog & Products

- **Products** — full CRUD, detail page, images, barcode printing
- Multi-unit support (piece / strip / box / carton conversions)
- Batch management with expiry, purchase cost, sale price, per-batch markup
- **Categories**, **Product types** (with icons), **Manufacturers**, **Storage locations** — CRUD
- **Bulk import** — CSV import with sample template, preview, revalidation
- Advanced catalog fields (generic name, strength, VAT %, description) — feature-gated

---

## 7. Inventory — `/inventory`

- Inventory overview & stock levels
- Stock movements ledger (audit of all stock changes)
- **Stock transfers** — between branches (create & list)
- Expiry management / expiring-soon tracking

---

## 8. Customers & Suppliers

- **Customers** — CRUD, addresses, balance/due tracking
- **Suppliers** — CRUD, detail page, dues, branch-scoped ledger (feature-gated)

---

## 9. Accounting — `/accounts`

- Ledger accounts (chart of accounts) — create, list, show
- Ledger entries (manual journal entries)
- Branch-aware ledger

---

## 10. Reports — `/reports`

Reports hub with export (CSV/Excel) and print support:

- Sales summary
- Purchase summary
- Inventory health
- Dues report
- Expiry report
- Supplier report
- Customer report
- Financial report
- Branch report
- User activity report

Smart filters and reusable report controls across all report pages.

---

## 11. HR & Workforce (feature-gated)

- **Employees** — CRUD, profiles, detail page
- **Attendance** — clock-in / clock-out (self & per-employee)
- **Payroll** — payroll runs, line items, finalize
- **Leave management** — leave types (CRUD) + leave requests (submit & approve)

---

## 12. Multi-Branch (feature-gated)

- Branches CRUD
- Branch switching
- Branch-scoped operational data (sales, purchases, ledger, transfers)
- Branch-level reporting

---

## 13. Settings & Team

- **Tenant settings** — pharmacy configuration, invoice rounding, feature toggles, currency
- **Team users** — invite/manage staff, assign roles
- **SMS** — SMS sending module
- **Promotions** — promotional campaigns CRUD
- **Support** — support/contact page (reads platform settings)

---

## 14. Localization

- Full English + Bengali (বাংলা) translations
- Per-tenant currency & money formatting
- Locale-aware number/date formatting

---

## Feature Flags (per-plan, per-tenant overridable)

Managed via `TenantFeatures`; can be set at the subscription-plan level and overridden per tenant:

| Flag | Purpose |
|------|---------|
| `wholesale_pricing` | Wholesale price tier |
| `markup_pricing` | Per-batch markup pricing |
| `bulk_import` | CSV catalog import |
| `advanced_catalog` | Extra catalog fields (generic, strength, VAT) |
| `multi_branch` | Multi-branch operations |
| `supplier_branch_ledger` | Branch-scoped supplier ledger |
| `employee_management` | HR employee module |
| `attendance` | Attendance tracking |
| `hr_payroll` | Payroll module |
| `barcode_camera_scan` | Camera barcode scanning in POS |
| `package_sales` | Package/bundle sales |

---

## Roles & Permissions (summary)

Granular permissions seeded via `RolePermissionSeeder`, including: products, categories, product types, manufacturers, storage locations, purchases (incl. all-branches), sales, returns, `pos.access`, inventory, stock transfers, customers, employees, accounting, reports (per report type + export/print + all-branches), billing, suppliers, team users, settings, branches, `sms.send`, promotions, and platform-level permissions (`platform.tenants`, `platform.subscriptions`, `platform.analytics`).

---

## Recently Added

- **PWA + Offline POS** (2026-07-18): installable app, offline catalog cache, offline sales queue with automatic idempotent sync. See migration `add_offline_client_id_to_sales_table`, `resources/js/offline/db.js`, `resources/js/composables/useOfflinePos.js`.
