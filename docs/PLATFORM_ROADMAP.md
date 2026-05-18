# Platform Super Admin — Roadmap & Progress

Multi-tenant pharmacy SaaS: **platform operators** manage the network; **tenant staff** run day-to-day pharmacy operations (POS, stock, etc.).

Last updated: 2026-05-16

---

## Status legend

| Symbol | Meaning |
|--------|---------|
| ✅ | Done |
| 🟡 | Partial |
| ⬜ | Not started |

---

## P0 — Operational (must-have)

| Item | Status | Notes |
|------|--------|-------|
| Platform shell + EN/BN locale | ✅ | `PlatformShellLayout`, `LocaleSwitcher`, `lang/en|bn` |
| Tenant lifecycle (create wizard, edit, suspend/unsuspend) | ✅ | 3-step wizard, `ProvisionTenantAction`, trial/subscription dates |
| Subscription plans CRUD | ✅ | `/platform/plans` |
| Dashboard KPIs + expiring soon | ✅ | `/platform/dashboard` |
| Audit log | ✅ | `/platform/audit` |
| Platform admins list | ✅ | `/platform/admins` (read-only) |
| Trial/subscription enforcement | ✅ | `EnsureTenantSubscriptionActive`, auto-block after trial |
| Subscription ends confirm UI | ✅ | Create/Edit tenants, default +1 year |
| Add owner later (Show page) | ✅ | `AttachTenantOwnerAction`, owner form |
| Suspend modal + reason | ✅ | `ConfirmModal`, audit `reason` |

---

## P1 — Support & growth (complete except signup queue)

| Item | Status | Notes |
|------|--------|-------|
| Tenant detail page | ✅ | `/platform/tenants/{id}` — subscription, users, activity |
| Impersonation | ✅ | “View as pharmacy”, banner, audit events |
| Owner email invite | ✅ | Invite + resend, `TenantOwnerInvitationNotification` |
| **Global platform settings** | ✅ | `/platform/settings` — trial default, support, SMS, feature flags |
| Tenant internal notes (platform-only) | ✅ | Edit + Show on tenant detail |
| Last login on users | ✅ | `users.last_login_at`, shown on tenant Show |
| Subscription history on Show | ✅ | `tenant_subscriptions` table on Show |
| Support page contact info | ✅ | Tenant `/support` reads platform settings |
| Public signup approval queue | ⬜ | Deferred (no public tenant signup yet) |

---

## P2 — Billing, analytics, compliance

| Item | Status | Notes |
|------|--------|-------|
| Billing (Stripe/bKash, invoices, MRR) | ✅ | `/platform/billing`, MRR, manual invoices, Stripe webhook stub |
| Payment fail → auto suspend rules | ✅ | Grace period in settings + `platform:suspend-payment-delinquent` |
| Cross-tenant analytics (aggregated) | ✅ | Dashboard: revenue, adoption, top tenants (30d) |
| Compliance (export/delete, retention) | ✅ | Tenant export ZIP, purge (suspended), retention settings + daily command |
| System health (queues, failed jobs) | ✅ | `/platform/health` + dashboard widget |

---

## P3 — Advanced

| Item | Status | Notes |
|------|--------|-------|
| Multi-region / localization defaults | ✅ | Platform settings: locale, timezone, country; new tenants inherit |
| Reseller / distributor hierarchy | ✅ | `/platform/resellers`, tenant assign on create/edit |
| Central drug catalog template | ✅ | `/platform/catalog-templates`, apply to pharmacy |
| Network-wide announcement banner | ✅ | `/platform/announcements`, tenant shell banner |

---

## Separate track: Tenant app (MediFlow)

14-step tenant navigation (POS, purchases, inventory, reports, etc.) — tracked separately from platform console. Tenant shell and modules exist; ongoing feature work is not listed in this file.

---

## Key routes (platform)

| Route | Purpose |
|-------|---------|
| `/platform/dashboard` | Overview |
| `/platform/tenants` | Pharmacy directory |
| `/platform/tenants/create` | Onboarding wizard |
| `/platform/tenants/{id}` | Detail |
| `/platform/tenants/{id}/edit` | Edit |
| `/platform/plans` | Subscription plans |
| `/platform/admins` | Platform administrators |
| `/platform/audit` | Audit log |
| `/platform/settings` | Global settings (P1) |
| `/platform/health` | Queue & failed jobs (P2) |
| `/platform/tenants/{id}/export` | Compliance data export (ZIP) |
| `/platform/tenants/{id}/purge` | Permanent tenant deletion |
| `/platform/billing` | MRR, invoices, payment actions |
| `/platform/resellers` | Reseller / distributor partners (P3) |
| `/platform/catalog-templates` | Central drug catalog templates (P3) |
| `/platform/announcements` | Network-wide tenant banners (P3) |
| `POST /api/webhooks/stripe/billing` | Stripe invoice events (optional) |

---

## What super admin should **not** do (without impersonation)

Daily POS, purchases, stock, customers, tenant ledger — those belong to tenant staff. Platform uses impersonation only for support.
