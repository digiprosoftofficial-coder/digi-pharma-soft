# SoftoPharma SEO & AI visibility growth plan

Goal: when people search **SoftoPharma**, **pharmacy management software**, or **pharmacy POS Bangladesh** on Google — and ask ChatGPT / Gemini / Claude / Perplexity — SoftoPharma should appear early with a clear, trusted answer.

**Current public URL (subdomain):** [https://softopharma.digiprosoft.com/](https://softopharma.digiprosoft.com/)

This doc is the working checklist. Code already covers on-site basics; most remaining work is **ops + content + citations**.

---

## Live subdomain settings

Production `.env` must match the subdomain (see `.env.production.example`):

```env
APP_NAME="SoftoPharma"
APP_URL=https://softopharma.digiprosoft.com
SESSION_DOMAIN=softopharma.digiprosoft.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=softopharma.digiprosoft.com
```

After deploy / config cache clear, verify:

- https://softopharma.digiprosoft.com/robots.txt
- https://softopharma.digiprosoft.com/sitemap.xml
- https://softopharma.digiprosoft.com/llms.txt

Search Console property: **URL-prefix** `https://softopharma.digiprosoft.com/`  
Submit sitemap: `https://softopharma.digiprosoft.com/sitemap.xml`

When you later move to a root domain (e.g. `softopharma.com`), update `APP_URL` + Sanctum + Search Console and 301-redirect the subdomain.

---

## What the product already does (on-site)

| Item | Status | Where |
|------|--------|--------|
| Editable SEO title / description / keywords | Done | Super Admin → Marketing site |
| Brand logo / favicon / OG image uploads | Done | Marketing site settings |
| Per-page SeoHead (home, pricing, demo, privacy, terms, get-started) | Done | `resources/js/Components/Marketing/SeoHead.vue` |
| JSON-LD Organization + WebSite + SoftwareApplication | Done | SeoHead |
| Dynamic `sitemap.xml` with lastmod | Done | `/sitemap.xml` |
| Dynamic `robots.txt` (allow marketing, disallow app areas) | Done | `/robots.txt` |
| `llms.txt` for AI crawlers | Done | `/llms.txt` |
| BD-focused default copy | Done | MarketingSiteSettings defaults + hero lang strings |

After deploy, verify on the live subdomain (list above). View-source home: canonical, description, JSON-LD scripts. `APP_URL` must equal `https://softopharma.digiprosoft.com` so sitemap/robots absolute URLs are correct.

---

## Phase 0 — Launch hygiene (do first)

- [x] Public subdomain live: `https://softopharma.digiprosoft.com/`
- [ ] Production `.env` `APP_URL` / Sanctum / session match subdomain (see above)
- [ ] `php artisan config:cache` (or clear) after `.env` change
- [ ] Google Search Console → property `https://softopharma.digiprosoft.com/` + sitemap submit
- [ ] Bing Webmaster Tools + sitemap
- [ ] Confirm Marketing Site Settings SEO title/description mention:
  - SoftoPharma
  - pharmacy management software
  - Bangladesh
- [ ] OG image uploaded (1200×630-ish) with logo + product name
- [ ] PageSpeed / mobile check on home + pricing (LCP, CLS)
- [ ] Later (optional): apex domain `softopharma.com` + 301 from subdomain

---

## Phase 1 — On-page content (weeks 1–2)

Keep one composition per section; do not turn the hero into a keyword dump.

- [ ] Keep hero eyebrow/sub copy mentioning SoftoPharma + pharmacy management software (already in lang files)
- [ ] Pricing page: short intro paragraph with “pharmacy POS Bangladesh”
- [ ] Demo page: clear who it’s for (retail pharmacies in BD)
- [ ] Add FAQ section answers that match real search questions:
  - What is SoftoPharma?
  - Is it for Bangladesh pharmacies?
  - Does it handle batch expiry / multi-branch?
- [ ] Add `/features` or deepen home `#features` with crawlable headings (H2/H3) for POS, inventory, expiry, purchases, accounts
- [ ] Internal links: Home ↔ Pricing ↔ Demo ↔ Get started

Optional later pages (high value):

- [ ] Blog index + 3 posts (expiry loss, pharmacy billing mistakes, multi-branch stock)
- [ ] “Pharmacy software Bangladesh” landing (careful not to thin-doorway)
- [ ] Case study pages (named pharmacies + cities when allowed)

---

## Phase 2 — Local & brand signals (weeks 2–4)

- [ ] Google Business Profile for DigiProSoft / SoftoPharma (BD address/phone if applicable)
- [ ] Consistent NAP on Facebook, LinkedIn, YouTube, website footer
- [ ] WhatsApp Business click-to-chat on marketing contact
- [ ] YouTube: 3–5 min SoftoPharma demo with title/description keywords + transcript
- [ ] Social posts that link to `/` and `/demo` (not only `/login`)

---

## Phase 3 — Citations for AI assistants (ongoing)

AI tools cite pages that other trusted sites mention. Push for:

- [ ] Product directory listings (Capterra / SoftwareSuggest / BD directories — only real profiles)
- [ ] 2–3 guest posts or partner pharmacy blog mentions with backlink
- [ ] Press note / launch post: “DigiProSoft launches SoftoPharma…”
- [ ] Authentic Q&A (Facebook groups / Reddit) with link to official site — no spam
- [ ] Public changelog or docs page AI can quote

Target one-liner every citation can reuse:

> SoftoPharma is pharmacy management software by DigiProSoft for Bangladesh pharmacies (POS, inventory, batch expiry, purchases, multi-branch, accounts).

Same line lives in `llms.txt` and default SEO description.

---

## Phase 4 — Measurement

Track monthly:

| Metric | Tool |
|--------|------|
| Brand query “SoftoPharma” impressions/clicks | Search Console |
| “pharmacy software Bangladesh” / “pharmacy POS” | Search Console |
| Sitemap indexed URL count | Search Console |
| Demo / get-started conversions | Analytics + CRM leads |
| Mentions in Perplexity / ChatGPT browsing | Manual spot checks |

---

## Honest ranking expectations

| Query type | Timeline |
|------------|----------|
| SoftoPharma (brand) | Days–weeks after index |
| pharmacy software Bangladesh | Months with content + links |
| Global “pharmacy management software” #1 | Unrealistic short-term |

Do not buy spammy backlinks. Prefer real pharmacies, partners, and useful content.

---

## Super Admin checklist (product)

1. Open **Marketing site** settings
2. Confirm site name `SoftoPharma`
3. SEO title includes Pharmacy Management Software + Bangladesh
4. Meta description ~150–160 characters, includes SoftoPharma + DigiProSoft
5. Keywords: pharmacy management software, pharmacy POS Bangladesh, medicine inventory software, …
6. Upload logo, icon/favicon, OG image
7. Save → hard refresh public home → verify favicon + meta

---

## Deploy notes (shared hosting / Docker)

After SEO code changes:

1. Local: `npm run build` (or Docker rebuild) so Vite assets include SeoHead updates
2. Ensure `/robots.txt` and `/sitemap.xml` hit Laravel (no stale static `public/robots.txt` overriding the route)
3. Production `APP_URL` must be `https://softopharma.digiprosoft.com` (current subdomain)

Related: `docs/DEPLOYMENT.md`, `docs/MARKETING_BRAND.md`, `.env.production.example`.
