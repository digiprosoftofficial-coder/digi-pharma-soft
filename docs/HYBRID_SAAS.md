# Hybrid SaaS (Leads, Demos, Self-Service, Payments)

Additive commercial layer on top of existing DigiProSoft pharmacy ops. **No existing tables were renamed or dropped.**

## Modules

| Area | Routes / UI |
|------|-------------|
| Public marketing | `/`, `/pricing`, `/demo`, `/get-started` |
| Leads CRM | `/platform/leads` |
| Demo booking | `/platform/demos` + public `/demo` |
| Payments (manual BD) | `/platform/payments` |
| Notifications | `/platform/notifications` + mail/DB |
| Tenant onboarding | `/onboarding` + shell banner |

## Roles

- `super admin` — full platform
- `sales` — leads, demos, payment view, analytics (platform team id `0`)

## Env vars

```
WHATSAPP_API_ENDPOINT=
WHATSAPP_API_KEY=
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
PLATFORM_2FA_REQUIRED=false
```

Payment merchant numbers and self-service toggles live in **Platform → Settings** (`platform_settings`).

## Scheduled jobs

- `platform:notify-subscription-lifecycle` daily 09:00 Asia/Dhaka intent (app timezone still UTC; tenant settings store `Asia/Dhaka`)

## Migrations (additive)

- `2026_11_04_100000_*` … `100400_*`

Rollback: `php artisan migrate:rollback --step=5` (drops new CRM/payment/OTP/notification columns/tables only).

## Manual payment flow

1. Customer submits bKash/Nagad/bank `transaction_reference` during signup (or admin creates invoice).
2. Row in `platform_payments` + open `platform_invoices`.
3. Super Admin verifies → reuses `MarkPlatformInvoicePaidAction` (idempotent via status + unique trx).
