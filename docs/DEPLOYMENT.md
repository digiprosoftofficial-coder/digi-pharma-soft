# Production deployment (reference)

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

## Backups

- Back up MySQL on a schedule; verify restores periodically.
- Encrypt off-site backups; restrict access to credentials and `.env`.

## Security review cadence

- Re-run checks for tenant leakage (missing scopes/policies), authorization gaps on new routes, N+1 queries on POS and reporting endpoints, and mass-assignment exposure whenever modules ship.
