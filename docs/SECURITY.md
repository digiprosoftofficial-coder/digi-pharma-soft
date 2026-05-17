# Security review checklist

Use this after each significant feature or before release:

1. **Tenant isolation** — Confirm new Eloquent models either extend `TenantModel` / use `TenantScoped`, or are intentionally central-only. Add automated tests that prove a user in tenant A cannot read tenant B rows via HTTP or direct `Model::query()` under a tenant session.
2. **Authorization** — Every new route should have middleware + Policy or explicit gate checks. Never rely on “hidden” UI alone.
3. **SQL injection** — Prefer Eloquent/query builder with bindings; avoid raw concatenated SQL.
4. **Mass assignment** — Keep `$fillable` tight; validate all write paths with Form Requests.
5. **CSRF / sessions** — Inertia web forms stay on the `web` stack; APIs use Sanctum appropriately.
6. **N+1** — Profile list and POS search endpoints; add eager loads and indexes.
7. **XSS** — Vue escapes by default; avoid `v-html` on untrusted strings; set a strict Content-Security-Policy at the edge when possible.
