# Copyright, Asset Licensing & Attribution Review — Phase 10

Method: inventories were taken from the **actual installed metadata** —
`composer.lock` license fields, `node_modules/*/package.json` license fields
and shipped LICENSE files, the compiled bundle in `public/build`, and the
project tree itself. Nothing below is inferred from a package name alone.
This review is an internal audit, **not a legal opinion and not a claim that
the project is legally cleared** in any jurisdiction.

## Inventory

### PHP dependencies (`composer.lock`, installed into `vendor/`, NOT redistributed in the handover ZIP)
- Production (73 packages): predominantly **MIT** (Laravel Framework 13.36,
  Symfony 8.1 components, Guzzle, Monolog, Carbon, Ramsey/Uuid, Flysystem…).
  **BSD-3-Clause**: league/commonmark, league/config, nikic/php-parser,
  tijsverkoyen/css-to-inline-styles, vlucas/phpdotenv.
  **Apache-2.0**: phpoption/phpoption.
  **Multi-licensed (BSD-3-Clause / GPL-2.0-only / GPL-3.0-only, recipient's
  choice)**: nette/schema, nette/utils (pulled in by league/config). The
  BSD-3 election is the standard reading of nette's multi-license; flagged
  here for transparency rather than silently labelled "MIT".
- Development-only (not shipped, installed only for build/test): Breeze,
  Pint, PHPUnit + sebastian/* (BSD-3), Mockery (BSD-3), Faker, Collision —
  all MIT or BSD-3.
- Every package carries its license text inside `vendor/` after
  `composer install`; because recipients install dependencies themselves,
  no PHP library code is copied by this project's distribution.

### JavaScript/CSS (npm) — direct dependencies
All **MIT**, verified from installed package metadata:
chart.js 4.5.1 (LICENSE.md present, © 2014–2024 Chart.js Contributors),
tailwindcss 3.4.19 (© Tailwind Labs, Inc.), @tailwindcss/forms 0.5.11,
@tailwindcss/vite 4.3.3, alpinejs 3.17.4 (MIT declared in metadata; the
installed package ships **no LICENSE file** — noted as an evidence nuance),
vite 8.3.4, laravel-vite-plugin 3.2.0, postcss, autoprefixer, concurrently.
Bundled transitively: @kurkle/color 0.3.4 (MIT, © 2018–2024 Jukka Kurkela),
@vue/reactivity (MIT).

### Fonts
- **Figtree** (SIL Open Font License 1.1, © 2022 The Figtree Project
  Authors) — loaded at runtime from **fonts.bunny.net** in both layouts;
  no font files are included in the project, so no OFL redistribution duty
  arises unless the font is ever self-hosted (noted in the notices file).

### Images, icons, branding
- **No stock photos, no third-party images, no icon libraries.** The only
  image file in the source is the Laravel skeleton's default `favicon.ico`.
- Sidebar "icons" are Unicode glyphs (▦ ✦ ◉ ☰) rendered by system fonts —
  not a licensed icon set.
- All UI text, layout and styling in the CRM modules are project-authored
  for this assignment, built on the Laravel 13 skeleton + Breeze scaffolding
  (MIT, © Taylor Otwell), which is also the source of the few inline SVG
  components that ship with Breeze.

## Findings & remediation

### F1 (Medium) Compiled bundle ships third-party code without its license notices
The minified `public/build` JS contains Chart.js, Alpine.js and
@kurkle/color (all MIT), and the CSS contains Tailwind's MIT preflight —
but production minification strips the license banners, and MIT requires
the copyright + permission notice to accompany copies.
**Remediation (done):** `docs/THIRD_PARTY_LICENSES.md` now collects the
copyright lines and the MIT text for every bundled library, the Figtree OFL
note, and the Laravel/Breeze attribution — and ships with the project.

### F2 (Low) Framework logo presented as the product logo on auth screens
The guest layout (login/password screens) rendered Breeze's default
`application-logo` component — the Laravel trademark artwork — as the
product's logo. Lawful under Breeze's MIT license, but misleading branding
for a deliverable named WebNet CRM.
**Remediation (done):** the guest layout now shows the project's own text
wordmark ("WebNet CRM", styled like the sidebar brand). The Breeze
component file itself remains in the tree, unused by the auth screens.

### F3 (Informational) Skeleton residue with third-party branding
`resources/views/welcome.blade.php` (the stock Laravel splash page at `/`)
and `resources/views/layouts/navigation.blade.php` (unused Breeze top-nav)
remain in the tree. Both are MIT skeleton material; neither is a license
problem. Whether the stock welcome page should be replaced before delivery
is a release-hygiene decision (later phase), recorded here for provenance.

### F4 (Informational) Skeleton AI-guideline files
`AGENTS.md` and `CLAUDE.md` at the project root are the Laravel 13
skeleton's own Laravel Boost guideline files (they ship with the framework
skeleton). They have been left exactly as shipped. Together with the
assignment-required AI usage summary (a final-phase deliverable, still to be
written truthfully), the AI-assisted nature of this project remains openly
documented — nothing has been removed to conceal it.

### F5 (Verified clean) Secrets & foreign files in the distributable source
- Source scan for hardcoded credentials/API keys across app/config/routes/
  views/database: **none found**. Demo credentials exist only in seeders
  and are labelled demo-only.
- The local `.env` contains a generated development `APP_KEY`; `.env` is in
  `.gitignore` and is **excluded from the planned ZIP** (to be re-verified
  at packaging). `.env.example` contains no key.
- No `.git` directory exists in the project (no history-leak surface).
- Dev artifacts (`.phpunit.result.cache`, `storage/` logs, the local SQLite
  file) are excluded from the planned ZIP contents; the packaging phase
  must re-verify the final archive listing.

## Planned ZIP contents — licensing view (for the final phase, not created yet)

Include: project source, `composer.lock`, prebuilt `public/build`, docs
(incl. this review + `THIRD_PARTY_LICENSES.md`), README, AI usage summary.
Exclude: `vendor/`, `node_modules/`, `.env`, local database file, caches and
logs. On that composition: no copyleft obligations are triggered (the only
GPL-flavoured metadata is the nette multi-license with a BSD-3 election, in
a package that is installed by the recipient, not copied); MIT/BSD/Apache
notice duties are met by the notices file plus each package's own license
file after installation.

## Unresolved / unverifiable

- Alpine.js's missing in-package LICENSE file means its MIT grant rests on
  the package metadata + upstream repository; recorded, not resolved.
- Trademark questions (e.g. use of the name "WebNet CRM" in relation to the
  commissioning company, Laravel's trademark in the footer credit line
  "Built for the WebNet Pakistan assignment") are outside a licensing audit
  of code assets and were not assessed.
- This review covers the project's declared/installed assets; it cannot
  prove a negative about unrecorded copying beyond the scans performed
  (no copied templates, branding or text were found in the project-authored
  UI and documentation).
