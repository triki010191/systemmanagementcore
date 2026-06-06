# Stitch Design Assets

Project ID: `14513084044404878557`

## Screens

| # | Screen | Screen ID | Folder |
|---|--------|-----------|--------|
| 1 | Design System | `asset-stub-assets_3a96f71153e44449b0c73cee156b999f` | `01-design-system/` |
| 2 | Login System | `c8f1407c758b4d948c2ad9f975d2ba34` | `02-login/` |
| 3 | Dasbor Eksekutif | `234583316c304dfb82e0349d293ae935` | `03-dashboard-eksekutif/` |
| 4 | Alat Penelusuran Jalur | `5ec4d33bb82d4577a4b142910d5a1c0d` | `04-alat-penelusuran-jalur/` |
| 5 | Manajemen Kabel | `6fc50d19ed42423e94d615fe08b2261c` | `05-manajemen-kabel/` |
| 6 | Peta Jaringan GIS | `e44acdc913604f55b3859fe37786b8a5` | `06-peta-jaringan-gis/` |

## Download

Stitch API requires authentication. Get API key from:
https://stitch.withgoogle.com → Settings → API Keys

```bash
export STITCH_API_KEY="your_key_here"
node scripts/fetch-stitch-assets.mjs
```

Each screen folder will contain:
- `screen.html` — Stitch-generated HTML (implementation reference)
- `screenshot.png` — Visual reference
- `metadata.json` — Full API response
- `export.fig` — Figma export (if available)

## Usage in Development

1. Use `screenshot.png` as visual target
2. Use `screen.html` as Blade conversion reference
3. Apply tokens from `docs/DESIGN.md` via Tailwind config
4. Never copy Stitch HTML blindly — convert to Blade components
