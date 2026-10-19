# Final Handover Report — Phase 14

WebNet CRM (Laravel 13.36.0, PHP 8.4.23) — final regression, documentation,
packaging and verification, performed 2026-10-15 in a Linux environment
(Ubuntu, SQLite). Statements below are limited to what was actually run.

## Final regression (development copy)
- `npm run build` — clean.
- `php artisan migrate:fresh --seed` — clean.
- `php artisan test` — **121 passed, 529 assertions, 0 failures.**
- Rendered-browser matrix re-run (headless Chromium, 6 widths × 15 screens
  = 90 combinations): **0 horizontal overflow, 0 unnamed controls, exactly
  one `<h1>` per page**; dashboard clean at 390/360/320 px; login page demo
  notice visible. This confirms the Phase 8 privacy fix, Phase 9
  accessibility fixes, Phase 10 licensing fix, and Phase 11 visual polish
  all remain intact in the final code.
- Locked definitions re-verified by the suite: won value by month uses
  `closed_at`; team-report overdue tasks are a current-state snapshot;
  conversion-report owner = lead assignee; deal probability = explicit
  override, otherwise current stage.

## Documentation delivered in this phase
- `README.md` — project README replacing the stock Laravel one: features,
  architecture, setup (Linux steps verified; **Windows steps included and
  explicitly labelled UNTESTED ON WINDOWS**), demo accounts and role
  differences, test commands with actual results, metric definitions,
  configuration/production notes, known limitations, docs index.
- `docs/DEMO_GUIDE.md` — scripted walkthrough of login/roles, dashboard,
  leads & conversion, companies & contacts, deals & pipeline, tasks &
  reminders, reports & CSV, user administration, audit logs.
- `docs/AI_USAGE.md` — truthful AI-assistance disclosure (tool used, the
  developer's decision/review role, what verification was and was not
  performed). Nothing in it is invented; unknowns are stated as unknowns.
- `docs/REQUIREMENTS_CHECKLIST.md` — finalized with Phase 14 evidence.
- All earlier phase reports and `docs/THIRD_PARTY_LICENSES.md` preserved
  unchanged.

## Demo data review (assignment item 3)
The seeder entry "Maryam Siddiqui" was reviewed. Evidence for its fictional
origin: the entire demo dataset was generated for this project in the
Phase 2 seeder (`database/seeders/CrmDemoSeeder.php`) as invented sample
data — Pakistani names, companies, `@*.example` reserved-domain emails and
fictional phone numbers; no real person's data was ever imported. The name
is a common Pakistani name that coincidentally shares the developer's first
name; it is **not** the developer (different surname) and not personal
data. Per the Phase 14 instruction, since its fictional demo-data origin is
established, the entry is retained. This reasoning was first recorded in
`docs/RELEASE_HYGIENE_REVIEW.md` (Phase 13).

## The archive
- **Filename:** `WebNet_CRM_Handover.zip`, containing a top-level
  `webnet-crm/` directory: full source, `composer.lock`, `package.json` +
  `package-lock.json`, 13 migrations, seeders/factories, 20 feature test
  files, all documentation and audit reports, `docs/THIRD_PARTY_LICENSES.md`,
  `.env.example`, and prebuilt `public/build` assets (3 asset files +
  manifest), so Node/npm are optional at setup.
- 285 files in the archive as inspected before this report was added
  (286 including this report).
- Exact byte size and SHA-256 of the final archive are recorded in the
  delivery note and in the accompanying sidecar file
  `WebNet_CRM_Handover.zip.sha256` (this report ships inside the archive,
  so it cannot contain its own container's final checksum).

## Documentation correction & re-verification (2026-10-15, same day)
A documentation-only correction was applied to `README.md` after the
initial build — **no application code or business logic changed**:
- The Windows setup is now **one consistent PowerShell sequence**
  (`Copy-Item`, `php artisan …`), including a guarded SQLite creation —
  `if (-not (Test-Path database\database.sqlite)) { New-Item … }` — which
  creates `database\database.sqlite` only if it does not already exist.
  The section remains labelled **UNTESTED ON WINDOWS**.
- The "dashboard works offline" wording was narrowed: Chart.js is bundled
  locally, while the Figtree webfont is requested externally, so pages are
  not fully offline (graceful font fallback applies).
- The README source was audited for literal Markdown escapes (`\*\*`,
  `\-`, `\|`, `\>`): **none were present** — the only backslash in the file
  is the Windows path separator inside the PowerShell code block — so no
  escape corrections were needed.
- The corrected README was rendered to HTML and checked (headings, demo
  table, PowerShell block, labels all render correctly), and every
  documentation path referenced in it was verified to exist.
- The archive was then **rebuilt**; the exclusion scan was repeated on the
  rebuilt archive (clean — see above), and the fresh-extraction
  verification (install → key → migrate/seed → full test suite → serve
  smoke check) was **re-run against the rebuilt archive** with the same
  passing results recorded in the section above.

## Exclusions — verified against the actual archive listing
Programmatic listing inspection of the built ZIP confirmed **none** of the
following are present: `.env` (only `.env.example`), any `*.sqlite` file,
`vendor/`, `node_modules/`, `*.log`, compiled Blade views or other
storage/framework generated content, `.phpunit.result.cache`, backups or
machine artifacts. A content scan of the archive's files found no
`APP_KEY` material, private keys, local absolute paths, or credentials.
Demo credentials exist only in `DatabaseSeeder`, clearly demo-only.

## Fresh-extraction verification (the decisive test)
The ZIP was extracted into a **new, empty directory** and the README setup
was followed exactly, with no access to the development database or any
excluded runtime file:

1. `composer install` (from `composer.lock`) — **succeeded**.
2. `cp .env.example .env` + `php artisan key:generate` — **succeeded**.
3. `touch database/database.sqlite` + `php artisan migrate --seed` —
   **succeeded** (fresh schema + full demo data).
4. `php artisan test` in the extracted copy — **121 passed,
   529 assertions** (identical to the development copy).
5. `php artisan serve` smoke check — `GET /login` → **200** with the demo
   notice rendered; `GET /dashboard` as guest → **302** redirect to login.

## Remaining limitations (unchanged, stated everywhere consistently)
- **Windows: not tested.** The README's Windows section is labelled
  untested; the developer runs the project locally herself as the final
  acceptance step.
- **MySQL: not tested.** Migrations are schema-builder portable by design;
  SQLite is the only verified database.
- True parallel-process conversion races not load-verified (atomic
  conditional-claim design + sequential tests).
- Audit log is application-level accountability, not tamper-proof.
- Reminders are in-app only; no scheduler, email or SMS.
- No external penetration test, production deployment, legal review, or
  full WCAG assessment has been performed.

## Requirements checklist status
All 13 assignment requirements are implemented and evidenced in
`docs/REQUIREMENTS_CHECKLIST.md` (item 13, documentation & demo readiness,
is completed by this phase's README, DEMO_GUIDE, AI_USAGE, and this
verified archive).
