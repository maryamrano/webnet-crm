# Final QA Report — WebNet CRM

Date: 2026-10-19 · Scope: remaining-QA pass over the Phase 14 codebase, plus two
surgical fixes (below). Environment: Linux build/verification environment
(PHP 8.4.23, SQLite). Results obtained in this environment are stated as such;
nothing here claims verification of the owner's Windows copy beyond the items
explicitly marked as owner-verified.

## Changes made during this QA pass

1. **Dashboard charts (defect, fixed).** Chart initialisation in
   `resources/views/dashboard.blade.php` ran during HTML parsing, but Chart.js
   arrives in the deferred Vite module bundle — `window.Chart` did not exist
   yet, the `if (window.Chart)` guard silently skipped initialisation, and all
   three charts never drew (in any environment). Fix (+4 lines, that file
   only): the init block now runs on `DOMContentLoaded`. Verified in headless
   Chromium for Admin and Sales Executive: `Chart.getChart()` returns an
   instance for all three canvases (doughnut/bar/bar), canvases size to their
   containers, and a rendered screenshot shows all three charts drawn with the
   correct figures. No Vite rebuild was needed (Blade-only change).
2. **Root route (reconciliation).** `routes/web.php`: `GET /` now returns
   `redirect()->route('login')` instead of the stock Laravel welcome view —
   adopting the behaviour of the project owner's Windows working copy (a
   deliberate local edit there). Authenticated visitors chain through the
   guest middleware to the dashboard. `tests/Feature/ExampleTest.php` was
   updated to assert the redirect (it previously asserted the stock 200).
   **Post-change hash proof:** after this single-line adoption, the file's
   SHA-256 is `251d7ba0577f2b875f89d822a41b45ced58cb3c22bcf808e279095f3cd0b44df`
   — exactly the hash the owner supplied for the Windows copy. The Windows
   file therefore differed from the build copy in that one line only; the
   routes file is now byte-identical across the Windows copy, this source,
   and the final ZIP.
3. **Documentation.** This report; `docs/REQUIREMENTS_CHECKLIST.md` refreshed.

No other source files were modified. No packages were installed or changed, no
configuration or seed data was altered.

## Test evidence

- Full suite after the changes: **121 passed (530 assertions)**, ~9 s
  (baseline before changes: 121 passed / 529 assertions — the extra assertion
  is the updated root-route test).
- Browser checks (headless Chromium, this environment):
  - Guest access to `/dashboard`, `/profile`, `/leads`, `/admin/users` all
    redirect to `/login`.
  - `/profile`: sections Profile Information / Update Password / Delete
    Account present with the expected fields; a name change saved and showed
    the "Saved" confirmation, then was reverted; server-side validation
    verified (empty name returns "The name field is required"); native
    `required` attributes also present.
  - Keyboard: login form tab order is logical (email → password → remember →
    forgot-password → submit, after initial focus); dashboard tab order starts
    with the "Skip to main content" link, then the sidebar in visual order.
    Every stop showed a visible focus indicator.
  - Logout and post-logout redirect: owner-verified manually on the Windows
    copy (2026-10-18); also covered by the automated auth tests.
- CRUD, role scoping (including direct-URL 403s), conversion, deal stages,
  task complete/reopen, reminders, reports, CSV exports, audit logging,
  validation and empty states: covered by the 121-test suite (per-module
  mapping in `docs/REQUIREMENTS_CHECKLIST.md`) — not re-performed manually;
  the suite was re-run in full only because source code changed.

## Security and configuration

- **Response headers** (captured from a real response, `GET /login`):
  present — `Cache-Control: no-cache, private`; session and XSRF cookies set.
  **Missing (stock Laravel defaults; documented, not silently fixed):**
  `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`; `Strict-Transport-Security` is only meaningful on an
  HTTPS deployment. `X-Powered-By: PHP/8.4.23` is exposed (server-level
  `expose_php` setting on the host). Recommendation for any real deployment:
  add a security-headers middleware (or web-server headers) and disable
  `expose_php`.
- **Cookies/session (from `config/session.php`):** lifetime 120 minutes,
  `http_only` on, `same_site=lax`; the secure flag follows
  `SESSION_SECURE_COOKIE` — set it to `true` for an HTTPS deployment (the
  production notes in `.env.example` say so).
- **APP_DEBUG:** `.env.example` ships `APP_DEBUG=false`, so a setup that
  follows the README runs with debug off. (The build environment's own local
  `.env` uses `true` for development and is never included in the ZIP.)
  The effective value on the owner's machine cannot be inspected from here —
  owner to confirm `APP_DEBUG=false` in the `.env` of any copy used for a
  public demonstration.
- **Dependency audits:** `composer audit` — **no security vulnerability
  advisories found**. `npm audit` — **could not be run in this environment**:
  the sandbox registry policy rejects the audit endpoint (HTTP 403
  `policy_denied`); it can be run on the owner's Windows machine
  (`npm audit`). The frontend dependency set is minimal (Chart.js is the only
  production dependency; the rest are build-time devDependencies).
- **CSRF / throttling / authorisation:** framework CSRF middleware on all
  state-changing routes (tokens present in every form); login throttling is
  the framework default (5 attempts); authorisation is server-side via
  policies, scoped queries and role middleware — all suite-tested, including
  direct-URL access attempts. Mass assignment is constrained by explicit
  `$fillable` whitelists on every model.
- **Demo accounts (recommendation, unchanged by this QA):** the seeded demo
  accounts use the password `password`, printed in the README for evaluation
  convenience. Rotate them before any exposure beyond the evaluation, and
  close temporary public tunnels (e.g. ngrok) after the evaluation.
- **Submission hygiene:** the final ZIP contains no `.env`, no database file,
  no `vendor/` or `node_modules/`, and no logs or caches (verified by listing
  the built archive). The application key is generated by the setup steps
  (`php artisan key:generate`), never shipped.

## Accessibility and usability

- Keyboard pass performed (above): logical order, skip link first, visible
  focus throughout; form labels and accessible names were verified in the
  Phase 9 rendering matrix (0 unlabelled controls across 15 screens × 6
  widths) and are unchanged by this pass's edits.
- **Screen-reader check: NOT performed** — no screen reader is available in
  this headless environment. Semantic structure (landmarks, one `h1` per page,
  labelled controls, chart text alternatives) is in place and
  automated-test-covered, but that is not a substitute for a screen-reader
  pass; this remains an honest gap.
- Responsive behaviour: verified in the Phase 9/11 matrix (1440/1280/768/
  390/360/320 px, 0 overflowing pages); the two surgical changes do not alter
  layout. Physical-device testing was not performed.

## Database and recovery

- Integrity (checked on a copy of the development database):
  `PRAGMA integrity_check` = ok; `PRAGMA foreign_key_check` = no violations.
  17 tables, 45 indexes; foreign keys on all CRM relations (leads 4,
  companies 2, contacts 1, deals 6, tasks 2, activities 1, audit_logs 1).
- Migrations/seeders reproduce the full dataset from empty:
  `php artisan migrate:fresh --seed` (verified repeatedly, and again by the
  final ZIP's fresh-extraction test run — 121 passing tests against the
  extracted copy).
- **Backup procedure (SQLite):** stop the application (or accept a
  point-in-time copy), then either copy `database/database.sqlite` or run
  `sqlite3 database/database.sqlite ".backup backup-YYYYMMDD.sqlite"`.
  **Restore:** replace `database/database.sqlite` with the backup file and
  restart the application. A backup/restore cycle was **tested on disposable
  copies** during this QA: restore completed, integrity ok, and all row
  counts identical (users 3, leads 20, companies 8, deals 12, tasks 15).
  The owner's live database was never touched. (For a future MySQL deployment
  the equivalent is `mysqldump` / restore — untested, like the rest of the
  MySQL path.)

## Requirement verdicts

Against the assignment scope as documented in this workspace
(`docs/REQUIREMENTS_CHECKLIST.md` maps each item to its implementation and
tests): items 1–13 — **Pass** (dashboard, leads, duplicate prevention,
companies/contacts/history, pipeline, tasks/reminders, roles, reports, CSV
exports, responsive UI, modelling/validation, sample data/setup, automated
tests). Cross-cutting: **MySQL compatibility — Not Verified** (SQLite only,
by design); **formal Windows acceptance walkthrough — Partial** (the owner
runs the app on Windows; the scripted walkthrough has not been recorded);
**clause-level comparison against the employer's original assignment text —
Not Verified**, because the original assignment text was never supplied to
this workspace. Any requirement whose wording exists only in that text cannot
be confirmed from here.

## Remaining submission risks

1. The original assignment text was never available (above) — the checklist
   is as strong as the documented scope, no stronger.
2. Demo credentials are published in the README by design; rotate after (or
   before) evaluation as appropriate, and close any public tunnel.
3. `APP_DEBUG` on the owner's demonstration machine is owner-side state —
   confirm it reads `false`.
4. `npm audit` has never been run (environment policy blocks it here); run
   it once on Windows for completeness.
5. No screen-reader pass and no physical-device pass exist — documented gaps,
   not hidden ones.

## Verification addendum (independent audit side chat, 2026-10-19)

This report and its deliverables were independently re-verified by the
separate audit session that produced the 2026-10-18 independent audit
(findings F-01…F-16). Additional behavioural evidence gathered there, in a
disposable copy of the final source: login throttling engages on the 6th
failed attempt; a tokenless POST is rejected with 419; a Sales Executive
injecting `created_by`/`role` fields cannot override server-side ownership;
lead conversion was executed end-to-end and a repeat conversion created no
duplicates; the delete-account modal is fully keyboard-operable (focus
enters the modal, Escape closes it); the dashboard on an empty database
renders its empty states with the chart script correctly omitted; and the
demo seeder is split-idempotent (users/stages use `updateOrCreate`, while
`CrmDemoSeeder` duplicates demo records if `db:seed` is re-run on an
existing database — seed once, on a fresh file, per the README).

Two documentation corrections were made during that verification and the
ZIPs were rebuilt afterwards: the README's expected suite result now reads
**121 passed (530 assertions)**, and the requirements checklist's test row
now reads **121 tests**. Earlier phase documents keep their historical
counts, which were accurate when written. Full detail: the independent
audit report, Note v12.
