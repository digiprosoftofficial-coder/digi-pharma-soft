# Roles & Permissions

Multi-tenant pharmacy SaaS roles. Permissions use **spatie/laravel-permission** with team scoping:

- **Platform roles** use team id `PlatformTeam::ID` (not a real tenant).
- **Pharmacy roles** are provisioned per tenant via `TenantRoleProvisioner`.

_Last updated: 2026-08-02_

**Source of truth**

- Permission catalog: `database/seeders/RolePermissionSeeder.php`
- Tenant role maps: `app/Support/Permission/TenantRoleProvisioner.php`
- Assign staff roles: Team → Users (`pharmacy owner` assigns tenant roles)

---

## Role overview

| Role | Scope | Typical user |
|------|--------|--------------|
| `super admin` | Platform | SaaS operator |
| `pharmacy owner` | One pharmacy (tenant) | Pharmacy owner |
| `manager` | One pharmacy | Branch / ops manager |
| `pharmacist` | One pharmacy | Catalog & stock staff |
| `cashier` | One pharmacy | Counter / POS staff |

---

## 1. Super Admin (`super admin`)

Platform-only. Manages the SaaS network, not day-to-day pharmacy selling (unless impersonating a tenant).

**Can**

- Manage tenants (create, edit, suspend, purge)
- Manage subscription plans, features, limits
- Platform billing / invoices
- Platform analytics & health
- Catalog templates, settings, announcements
- Attach / invite pharmacy owners
- Impersonate a tenant (“View as pharmacy”)

**Cannot (by design as a tenant staff role)**

- Does not use the tenant POS as a normal pharmacy employee
- Access is via `/platform/*` (+ optional impersonation)

**Permissions:** all permissions in the system (including `platform.tenants`, `platform.subscriptions`, `platform.analytics`).

---

## 2. Pharmacy Owner (`pharmacy owner`)

Full control of one pharmacy tenant.

**Can**

- POS / sales / returns / package sales (if plan feature enabled)
- Products, categories, types, manufacturers, storage locations, bulk import
- Purchases, supplier bills, purchase returns
- Inventory & stock transfers (if multi-branch)
- Customers, suppliers, employees, HR/attendance (if plan features enabled)
- Accounting, reports (including export/print where permitted)
- Promotions, SMS (if used)
- Team users (create/edit and assign roles)
- Settings & branches
- Tenant billing management (`billing.manage`)
- Always record supplier payments

**Cannot**

- Platform admin (`platform.*` permissions are excluded)

**Permissions:** all non-platform permissions for that tenant.

---

## 3. Manager (`manager`)

Nearly full operational access; slightly less than owner.

**Can**

- Same day-to-day areas as owner for sales, purchases, catalog, inventory, people, reports, team, settings, branches (based on synced permissions)

**Typically cannot**

- Platform permissions
- `billing.manage`
- `purchases.view_all_branches`
- `reports.view_all_branches`
- Supplier bill payment unless tenant setting **managers can pay** is enabled (`SupplierPaymentSettings`)

**Permissions:** all except platform + `billing.manage` + the two “view all branches” permissions above.

---

## 4. Pharmacist (`pharmacist`)

Catalog, stock, and limited visibility — **not** a POS cashier role.

**Can**

- View/manage products, categories, product types, manufacturers, storage locations
- View/manage inventory
- View customers
- View sales; manage returns
- View stock transfers, suppliers, purchases
- View branches
- Reports: sales, inventory, expiry, customer, supplier + print

**Cannot**

- POS access (`pos.access` is not granted)
- Team users, settings manage, billing, accounting manage
- Full purchase manage (only `purchases.view`)

**Permissions (exact list):**

- `products.view`, `products.manage`
- `categories.view`, `categories.manage`
- `product_types.view`, `product_types.manage`
- `manufacturers.view`, `manufacturers.manage`
- `storage_locations.view`, `storage_locations.manage`
- `inventory.view`, `inventory.manage`
- `customers.view`
- `reports.view`, `reports.sales`, `reports.inventory`, `reports.expiry`
- `reports.customer`, `reports.supplier`, `reports.print`
- `sales.view`, `returns.manage`
- `stock_transfers.view`, `suppliers.view`, `purchases.view`
- `branches.view`

---

## 5. Cashier (`cashier`)

Counter selling only.

**Can**

- Open POS and complete sales (`pos.access`)
- View products (search / sell)
- View customers
- View sales list

**Cannot**

- Purchases, inventory manage, catalog manage
- Reports, accounts, team, settings, branches manage
- Returns manage (not in cashier set)

**Permissions (exact list):**

- `pos.access`
- `products.view`
- `customers.view`
- `sales.view`

---

## Capability matrix (practical)

| Area | Super Admin | Owner | Manager | Pharmacist | Cashier |
|------|-------------|-------|---------|------------|---------|
| Platform console | Yes | No | No | No | No |
| POS sale | Via impersonation only | Yes | Yes | No | Yes |
| Product manage | — | Yes | Yes | Yes | No |
| Purchase manage | — | Yes | Yes | View only | No |
| Inventory manage | — | Yes | Yes | Yes | No |
| Returns | — | Yes | Yes | Yes | No |
| Reports | — | Full | Broad | Limited | No |
| Team / settings | — | Yes | Yes | No | No |
| Billing (tenant) | Platform billing | Yes | No | No | No |
| Supplier pay | — | Always | If setting on | No | No |

---

## All permission names

Defined in `RolePermissionSeeder`:

```
products.view, products.manage
categories.view, categories.manage
product_types.view, product_types.manage
manufacturers.view, manufacturers.manage
storage_locations.view, storage_locations.manage
purchases.view, purchases.manage, purchases.view_all_branches
sales.view
returns.manage
pos.access
inventory.view, inventory.manage
stock_transfers.view, stock_transfers.manage
customers.view, customers.manage
employees.view, employees.manage
accounting.view, accounting.manage
reports.view, reports.sales, reports.purchase, reports.inventory, reports.expiry
reports.supplier, reports.customer, reports.finance, reports.branch, reports.activity
reports.export, reports.print, reports.view_all_branches
billing.manage
suppliers.view, suppliers.manage
team.users.view, team.users.manage
settings.view, settings.manage
branches.view, branches.manage
sms.send
promotions.view, promotions.manage
platform.tenants, platform.subscriptions, platform.analytics
```

---

## How roles are created

1. `RolePermissionSeeder` creates global permissions + `super admin`.
2. When a tenant exists / is provisioned, `TenantRoleProvisioner::provision($tenantId)` creates:
   - `pharmacy owner`
   - `manager`
   - `cashier`
   - `pharmacist`
3. New staff users get one of the tenant roles from **Team → Users**.

---

## Notes

- Plan feature flags (e.g. multi-branch, HR, camera barcode scan) can hide UI even when a role has the related permission.
- UI navigation is gated with `can('permission.name')` (see `TenantSidebarNav.vue` and related pages).
- After changing role maps in code, re-run permission seeding / re-provision tenants so existing role rows sync.
