# Production deployment (reference)

## Capacity — how many users is it safe for?

These are practical guidelines, not hard limits. "Active users" = people clicking
around at the same time, not total registered accounts.

| Hosting | Safe concurrent active users | Total pharmacies (rough) | Notes |
|---------|------------------------------|--------------------------|-------|
| Basic shared cPanel (DB sessions + DB cache + `sync` queue) | ~30–50 | up to ~50–100 light pharmacies | Every request writes session+cache to MySQL; email sends inside the request. OK for pilot/demo. |
| Good shared cPanel (DB queue + cron worker, tuned MySQL) | ~100–300 | a few hundred | Email/invites moved to background via `QUEUE_CONNECTION=database` + cron. Still single MySQL. |
| VPS / cloud + Redis + Supervisor worker | 1,000s | thousands | `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`. This is the target for scale. |

Why shared hosting caps out:

- `SESSION_DRIVER=database` + `CACHE_STORE=database` → **every request** hits MySQL
  for session and cache reads/writes. A few hundred concurrent users saturate the
  shared DB before PHP is the bottleneck.
- `QUEUE_CONNECTION=sync` → OTP/invite/notification emails run **inside** the web
  request, holding a PHP-FPM worker until SMTP responds.
- Shared hosting limits PHP workers and MySQL `max_connections`.

Rule of thumb: **plan the move to a VPS + Redis before ~300 concurrent users** (or
earlier if POS is used heavily all day across many branches). For "thousands of
users" you must be on Tier B.

See `.env.production.example` for the Tier A / Tier B switch.

## PHP / app

- Set `APP_ENV=production`, `APP_DEBUG=false`, and a strong `APP_KEY`.
- Run `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
- Point `QUEUE_CONNECTION=redis` (recommended) and run queue workers under Supervisor.

## Scheduler

- Cron entry: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`
- This project schedules `tenants:suspend-expired` daily at 01:15 server time.

## Redis

- Use Redis for cache and queues in production (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`).
- Optionally use Redis for sessions if you need horizontal scaling (`SESSION_DRIVER=redis`).

## Nginx (sketch)

- Route PHP to `php-fpm` on a Unix socket or TCP port.
- Serve `public/` as the web root; deny direct access to other paths.
- Terminate TLS at Nginx (Let’s Encrypt) and enable HTTP/2.

## Supervisor (queue worker sketch)

```ini
[program:saas-pharmacy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/saas-pharmacy/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/saas-pharmacy-worker.log
```

## Inertia / Sanctum SPA

- Set `SANCTUM_STATEFUL_DOMAINS` to your SPA host(s) (comma-separated) so cookie auth works for first-party XHR from the Vue app.

### Current production subdomain

Live marketing/app host: **https://softopharma.digiprosoft.com/**

```env
APP_URL=https://softopharma.digiprosoft.com
SESSION_DOMAIN=softopharma.digiprosoft.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=softopharma.digiprosoft.com
```

Copy from `.env.production.example`. After changing `.env` on the server:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Confirm SEO endpoints: `/robots.txt`, `/sitemap.xml`, `/llms.txt`.

If any plan enables **photo search**, set `GEMINI_API_KEY` (see `.env.production.example` and `docs/POS_SCAN_AND_PHOTO_SEARCH.md`).

## Backups

- Back up MySQL on a schedule; verify restores periodically.
- Encrypt off-site backups; restrict access to credentials and `.env`.

## Security review cadence

- Re-run checks for tenant leakage (missing scopes/policies), authorization gaps on new routes, N+1 queries on POS and reporting endpoints, and mass-assignment exposure whenever modules ship.
