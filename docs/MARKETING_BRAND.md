# SoftoPharma marketing brand tokens

Public home / pricing / demo / get-started surfaces use a scoped palette in `resources/css/marketing.scss` (`.marketing-shell`). Do not reuse these as tenant/platform Bootstrap theme defaults unless intentionally aligning brand.

## Colors

| Token | Value | Use |
|-------|-------|-----|
| `--sp-forest` | `#123d32` | Display headlines, strong text; also `theme-color` in `resources/views/app.blade.php` |
| `--sp-brand` | `#287a57` | Primary green — CTAs, links, eyebrows |
| `--sp-brand-hover` | `#1f6547` | Primary button / link hover |
| `--sp-sage` | `#e7f0e9` | Soft green surfaces / chips |
| `--sp-cream` | `#f8f8f4` | Page background |
| `--sp-charcoal` | `#252c28` | Body text |
| `--sp-muted` | `#5a655e` | Secondary / lead copy |
| `--sp-line` | `rgba(18, 61, 50, 0.12)` | Borders and dividers |
| `--sp-white` | `#ffffff` | Cards, contrast panels |

## Typography

| Token | Stack | Use |
|-------|-------|-----|
| `--sp-font` | `'Manrope', system-ui, sans-serif` | UI body and labels |
| `--sp-display` | `'Fraunces', Georgia, 'Times New Roman', serif` | Hero / section titles |

## Radius

| Token | Value |
|-------|-------|
| `--sp-radius` | `0.35rem` |

## Source of truth

- CSS variables: `resources/css/marketing.scss`
- Layout: `resources/js/Layouts/MarketingLayout.vue`
- Home: `resources/js/Pages/Marketing/Home.vue`
