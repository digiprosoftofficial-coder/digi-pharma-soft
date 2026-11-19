# POS search styles — barcode scan & photo search

Goal: document how **normal search**, **barcode camera scan**, and **photo medicine search** work in SoftoPharma POS — what was added, how Super Admin controls them per plan, and how to operate / extend them later.

Related: `docs/FEATURES.md`, `docs/DEPLOYMENT.md`, `.env.example`, `.env.production.example`.

---

## 1. Three ways to find a product on POS

| Style | What the user does | Needs plan feature? | Needs internet / API? | Best for |
|-------|--------------------|---------------------|------------------------|----------|
| **Normal search** | Type name / generic / SKU in the search box | Always on | Online search API (offline cache fallback exists) | Everyday typing |
| **Hardware barcode scanner** | Focus search box → scan with USB/Bluetooth gun | Always on (no extra flag) | Same as normal search | Counter sales, fastest |
| **Camera barcode scan** | Tap **Scan barcode** → point phone/laptop camera at barcode | `barcode_camera_scan` | Browser camera; product lookup via catalog | Mobile / no gun |
| **Photo search (AI)** | Tap **Photo search** → photograph the pack label | `photo_search` | Yes — Gemini Vision API | When barcode is missing / hard to scan |

All four paths end in the same place: fill the search box / results list → pharmacist adds the line to the cart (except unambiguous barcode matches, which auto-add).

---

## 2. How each path works (flow)

### 2.1 Normal search + hardware scanner

```
Search box (#pos-product-search)
  → @input debouncedSearch → GET /catalog/product-search?q=…
  → @keydown.enter → onSearchEnter → tryAddFromSearch()
```

Hardware scanners act like a keyboard: they type digits then press Enter. No special library is required.

Exact barcode match is preferred when adding:

- Frontend: `tryAddFromSearch()` looks for `r.barcode === term`
- Backend: `ProductRepository::searchByTerm()` matches `barcode` **exactly** (name/SKU use `LIKE`)

**Requirement:** every sellable product should have `products.barcode` filled, or hardware/camera barcode scan will not find it.

### 2.2 Camera barcode scan (`html5-qrcode`)

```
Plan feature barcode_camera_scan = true
  → POS shows “Scan barcode” button
  → startBarcodeScanner()
       → dynamic import('html5-qrcode')   // lazy — not in first paint bundle
       → Html5Qrcode starts rear camera
  → onBarcodeScanned(text)
       → stop camera → q = barcode → runSearch → tryAddFromSearch()
```

Feature flag check (frontend):

```js
page.props.features?.barcode_camera_scan
```

Shared from Laravel via `TenantFeatures::shareForInertia()`.

### 2.3 Photo search (Gemini Vision)

```
Plan feature photo_search = true  AND  GEMINI_API_KEY set
  → POS shows “Photo search” button
  → openPhotoSearchPicker() → <input type="file" capture="environment">
  → compressPhotoForSearch() (max edge 1280px, JPEG ~0.82)
  → POST /catalog/product-image-search  (multipart image, throttle 20/min)
       → TenantFeatures::photoSearchEnabled()
       → GeminiMedicineRecognizer::recognize()
       → ProductRepository::searchByTerm(query or barcode)
  → UI shows results; pharmacist must confirm
       (auto-add ONLY if exact barcode + single product + confidence ≥ 0.7)
```

Images are **not stored** by the app. Bytes are sent to Google Gemini for that request only.

---

## 3. What was added (file map)

### Plan / feature plumbing

| File | Role |
|------|------|
| `app/Support/Tenant/TenantFeatures.php` | Constants `BARCODE_CAMERA_SCAN`, `PHOTO_SEARCH`; helpers; Inertia share |
| `app/Http/Controllers/Central/PlatformPlanController.php` | Validates & saves `features.barcode_camera_scan`, `features.photo_search` |
| `resources/js/Pages/Platform/Plans/Form.vue` | Super Admin checkboxes |
| `database/seeders/SubscriptionPlanSeeder.php` | Starter off; Professional / Enterprise on for both scan features |
| `lang/en/platform.php`, `lang/bn/platform.php` | Feature labels + help text |
| `lang/en/sales.php`, `lang/bn/sales.php` | POS button / error strings |
| `resources/js/Pages/Marketing/Pricing.vue` | Public pricing bullets |

### Camera barcode (existing + performance)

| File | Role |
|------|------|
| `resources/js/Pages/Pos/Index.vue` | Camera UI + `loadHtml5Qrcode()` dynamic import |
| `vite.config.js` | Separate `barcode` chunk; POS SW timeout; `/build/` StaleWhileRevalidate |
| npm package `html5-qrcode` | Browser barcode/QR decoding |

### Photo search (new)

| File | Role |
|------|------|
| `config/services.php` → `gemini` | `api_key`, `model`, `endpoint`, `timeout` |
| `app/Support/Catalog/GeminiMedicineRecognizer.php` | Calls Gemini Vision; returns structured `{brand_name, generic_name, strength, barcode, query, confidence}` |
| `app/Http/Controllers/Api/Catalog/ProductImageSearchController.php` | Auth, feature gate, upload validation, search, JSON response |
| `routes/web.php` | `POST /catalog/product-image-search` → `catalog.product-image-search` |
| `tests/Feature/Catalog/ProductImageSearchTest.php` | 503 / 403 / happy path / barcode prefer / 422 / 502 |
| `.env.example`, `.env.production.example` | `GEMINI_*` comments |

### Shared catalog search (used by all styles)

| File | Role |
|------|------|
| `app/Http/Controllers/Api/Catalog/ProductSearchController.php` | `GET /catalog/product-search` |
| `app/Domain/Catalog/Repositories/ProductRepository.php` | `searchByTerm()` |
| `resources/js/composables/useOfflinePos.js` | Online + offline product search helper |

---

## 4. Super Admin — how to turn features on/off

1. Log in as Super Admin → **Platform → Plans**.
2. Edit a plan (e.g. Professional).
3. Under features:
   - **Camera barcode scanning** → `barcode_camera_scan`
   - **Photo medicine search** → `photo_search`
4. Save. Tenants on that plan get the matching POS buttons after next page load (Inertia shared `features`).

Default seed:

| Plan | `barcode_camera_scan` | `photo_search` |
|------|------------------------|----------------|
| Starter | off | off |
| Professional | on | on |
| Enterprise | on | on |

Existing production plans created before this feature need the checkbox ticked manually (or re-run / merge seeder carefully).

---

## 5. Gemini setup (photo search only)

1. Open [https://aistudio.google.com/apikey](https://aistudio.google.com/apikey) with any Google account.
2. Create an API key (new Cloud project is fine).
3. Put in server `.env`:

```env
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-2.0-flash
GEMINI_TIMEOUT=20
```

4. Refresh config:

```bash
php artisan config:cache
# Docker:
docker compose exec app php artisan config:cache
```

5. Without a key, POS button may still show (if plan allows) but API returns **503** with “not configured”.

**Cost note:** Gemini Flash is cheap; free quota covers light use. Heavy daily photo volume may need Google Cloud billing enabled. Camera barcode scan has **no** Gemini cost.

---

## 6. Deploy / ops checklist

### After pulling this code

```bash
# Shared hosting: build locally, upload public/build + app code
npm run build

# Docker
docker compose up -d --build web app
```

### Production `.env`

- `GEMINI_API_KEY` if any plan uses photo search
- Keep `APP_URL` HTTPS — camera APIs require secure context (HTTPS or localhost)

### Verify

| Check | Expected |
|-------|----------|
| Plan edit page | Both checkboxes visible |
| Tenant on Professional | POS shows Scan barcode + Photo search |
| Tenant on Starter | Neither camera/photo button (normal search + hardware gun still work) |
| Photo without key | 503 JSON message |
| `php artisan test --filter=ProductImageSearchTest` | 6 passed |

### Security / safety (do not weaken casually)

- Photo path rate-limited: `throttle:20,1`
- Upload: jpeg/png/webp, max 4 MB server-side
- Client compresses before upload
- Pharmacist confirmation for name guesses (wrong medicine risk)
- Never commit `GEMINI_API_KEY` to git

---

## 7. Frontend UX notes (POS)

File: `resources/js/Pages/Pos/Index.vue`

- Buttons live in `.pos-product-search-actions` next to “All Products”.
- Opening photo search stops an active barcode camera session.
- Typing in the search box clears `photoSearchError`.
- Adding a cart line clears photo error.
- `html5-qrcode` is **lazy-loaded** so first POS paint stays smaller.

---

## 8. Future work — how to change things safely

### Enable photo search on an existing live plan

Super Admin → Plans → tick **Photo medicine search** → ensure `GEMINI_API_KEY` on server → `config:cache`.

### Change Gemini model

Set `GEMINI_MODEL` (e.g. newer Flash). Redeploy/config cache. No code change required if the generateContent JSON API stays compatible.

### Add another search style (e.g. voice)

Follow the same pattern as `photo_search`:

1. Constant + helper + `shareForInertia` in `TenantFeatures`
2. Validate/save in `PlatformPlanController`
3. Checkbox in `Plans/Form.vue` + seeder + lang
4. Gate in controller + `v-if` on POS
5. Feature test for 403 when off

### Improve recognition accuracy

Edit prompt + JSON schema in `GeminiMedicineRecognizer` (`PROMPT` constant). Keep `responseMimeType: application/json`. Re-run `ProductImageSearchTest` with `Http::fake`.

### Offline photo search?

Not supported. Photo search needs Gemini. Offline POS still supports cached name/barcode search via `useOfflinePos`.

### Troubleshooting

| Symptom | Likely cause |
|---------|----------------|
| No Photo / Scan buttons | Plan feature off, or Inertia `features` stale — hard refresh |
| Camera never starts | Denied permission, or not HTTPS |
| Photo → 503 | Missing `GEMINI_API_KEY` / config cache old |
| Photo → 502 | Gemini quota/network/model name wrong — check `storage/logs` |
| Photo → 403 | Plan `photo_search` false |
| Barcode finds nothing | Product `barcode` column empty or mismatched digits |
| Auto-add from photo never happens | By design for name guesses; only exact barcode + 1 hit + confidence ≥ 0.7 |

---

## 9. Quick mental model

```
Super Admin plan flags
        │
        ▼
TenantFeatures → Inertia props.features
        │
        ├─ barcode_camera_scan → html5-qrcode camera → same search as gun
        └─ photo_search        → Gemini Vision → search → confirm in UI

Normal typing + USB gun always available (no flag).
```

When in doubt: **barcode (gun or camera) for speed and certainty; photo search as a fallback when the pack has no usable barcode.**
