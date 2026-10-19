# WebNet CRM

A single-organization customer relationship management system built with
**Laravel 13** and **PHP 8.4**: leads and conversion, companies and contacts,
a staged sales pipeline, tasks and reminders, role-based access for Admin /
Manager / Sales Executive, a live dashboard, reports with CSV export, and an
application-level audit trail.

> **Demonstration system.** All bundled data is fictional sample data.
> Demo credentials are for local demonstration only and must never be used
> in production.

---

## Features

- **Leads** - sources, statuses, assignment, search/filters, duplicate
  warnings (never auto-merge), and a conversion flow that turns a lead into
  a company + contact (+ optional deal) in a single transaction.
- **Companies & contacts** - nested contacts, one primary contact per
  company, communication-history (activity) timelines on every record.
- **Deals & pipeline** - configurable pipeline stages with probabilities,
  a pipeline board with per-card stage controls, and weighted pipeline
  values (deal probability override wins; otherwise the current stage's
  probability; Won = 100%, Lost = 0%).
- **Tasks & reminders** - priorities, due dates, optional reminders, tasks
  linked to leads/companies/contacts/deals, complete/reopen, and an in-app
  reminders page (overdue / due within 48 hours / reminder time reached).
  Reminders are evaluated when the page loads; there is no email/SMS
  sending and no background scheduler.
- **Roles & permissions (server-side)** - Admin, Manager, Sales Executive.
  Sales Executives only see records they own, are assigned to, or created.
  There is no public registration; Administrators create user accounts.
- **Dashboard** - live metrics and Chart.js charts. Chart.js is bundled
  locally, so the charts need no CDN; note the Figtree webfont is still
  requested externally (fonts.bunny.net), so pages are not fully offline -
  without it the UI simply falls back to a system font.
- **Reports** - lead conversion (by source and by assignee), pipeline by
  stage, and team performance - all filterable, all exportable to CSV
  (exports guard against spreadsheet formula injection).
- **Audit trail** - an Admin-only, append-only viewer recording who did
  what. It is an application-level accountability log; it is **not**
  tamper-proof (see `docs/SECURITY_REVIEW.md`).

Details and evidence: `docs/REQUIREMENTS_CHECKLIST.md`.

## Architecture

Standard Laravel MVC with an emphasis on server-side authorization:

- `app/Models` - Eloquent models with a `visibleTo` query scope per role.
- `app/Policies` - per-model authorization; the scope and the policy agree
  by construction and both are covered by tests.
- `app/Http/Controllers` - thin controllers; Form Requests validate input.
- `app/Services` - `LeadDuplicateFinder` (visibility-scoped duplicate
  warnings), `AuditLogger` (explicit audit capture; no observers), and
  report dataset builders shared by the HTML views and the CSV exports,
  so an export always matches the report on screen.
- `app/Support/CrmParentTypes.php` - the single whitelist of record types
  that tasks and activities can attach to.
- Views: Blade + Tailwind CSS; Alpine.js for small interactions; Chart.js
  bundled at build time (no CDN at runtime). The only external runtime
  request is the Figtree webfont (SIL OFL 1.1) from fonts.bunny.net.

## Prerequisites

- **PHP 8.4** (the project was developed and tested on PHP 8.4.23;
  Laravel 13 requires PHP ≥ 8.3) with the standard Laravel extensions
  (incl. `pdo_sqlite` for the bundled setup).
- **Composer 2**.
- **Node.js 20+ and npm - optional.** Prebuilt frontend assets are included
  in `public/build`, so you only need Node if you change frontend source.

## Setup (verified on Linux)

These exact steps were executed and verified in a Linux environment
(Ubuntu, PHP 8.4.23, SQLite) - including from a fresh extraction of the
handover ZIP (see `docs/FINAL_HANDOVER_REPORT.md`).

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # create the empty SQLite file if missing
php artisan migrate --seed
php artisan serve                # http://127.0.0.1:8000
```

Then open http://127.0.0.1:8000 and log in with a demo account below.
To reset the demo data at any time: `php artisan migrate:fresh --seed`.

### Setup on Windows — Verified

The application has been successfully set up and tested on Windows 11 using PHP 8.4 and Composer 2, including from a fresh extraction of the final ZIP.

**Prerequisites**

- Windows 10 or 11
- PHP 8.4 with the required Laravel extensions, including `pdo_sqlite`
- Composer 2
- Git (recommended)
- Node.js 20+ and npm are optional because prebuilt frontend assets are included in `public/build`.

**Installation**

Open PowerShell in the extracted project folder and run:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm ci
npm run build
if (-not (Test-Path database\database.sqlite)) {
    New-Item database\database.sqlite -ItemType File
}
php artisan migrate --seed
php artisan serve
```

Open http://127.0.0.1:8000 in your browser and log in using one of the demo accounts listed above.

**Database:** These instructions use SQLite, the default local-development database. Ensure the `pdo_sqlite` extension is enabled in PHP.

The final submission ZIP was freshly extracted and successfully run on Windows 11.

**Database:** These instructions use SQLite, the default local-development database. Ensure the `pdo_sqlite` extension is enabled in PHP.

The final submission ZIP was freshly extracted and successfully run on Windows 11.


## Demo accounts

All demo accounts use the password **`password`** (demo-only).

|  Role           | Email                    | Can do                                                                                                      |
| --------------- |------------------------- |-------------------------------------------------------------------------------------------------------------|
| Admin           | `admin@webnetcrm.test`   | Everything: user management, audit-log viewer, delete any record                                            |
| Manager         | `manager@webnetcrm.test` | Sees all CRM data; manages leads/deals/tasks/activities; cannot manage users or delete companies/contacts   |
| Sales Executive | `sales@webnetcrm.test`   | Sees **only their own** records (owned / assigned / created); own-numbers-only dashboard and reports        |

A guided walkthrough is in `docs/DEMO_GUIDE.md`.

## Running the tests

```bash
php artisan test
```

Expected result at handover: **121 passed (530 assertions)** on PHP 8.4 /
SQLite (Linux). The suite covers authorization boundaries, role data
scoping, lead conversion (including the concurrency guard), the deal
probability rule, metric definitions, CSV export safety, the audit trail,
privacy scoping of duplicate matching, and key accessibility invariants.

## Reports - metric definitions (as implemented and tested)

- **Won value by month** is grouped by each deal's `closed_at`.
- **Team-report overdue tasks** are a *current-state snapshot* (open tasks
  past due right now), distinct from the date-filtered period metrics.
- **Conversion-report "owner"** means the lead's **assignee**.
- **Deal probability:** an explicit per-deal value wins; otherwise the
  deal's *current* pipeline stage probability applies. Weighted values
  always use this effective probability.

## Configuration notes

- `.env.example` ships safe defaults for a local demo (`APP_DEBUG=false`,
  `DB_CONNECTION=sqlite`, `MAIL_MAILER=log` - password-reset and other
  mail is written to the log, not sent; configure a real mailer to send).
- For a real deployment: set `APP_ENV=production`, `APP_URL` to the HTTPS
  address, and `SESSION_SECURE_COOKIE=true` (also noted in `.env.example`).
- **MySQL:** verified on **MySQL 8.0.46** (19 October 2026). From a fresh
  extraction of the final ZIP, all 13 migrations and both seeders completed
  on an empty MySQL database, the full test suite passed against MySQL
  (**121 tests, 530 assertions**), and login, dashboard and the core pages
  were served from MySQL data. **MySQL 8.4 was not separately tested.**
  SQLite remains the default for local development.

## Known limitations (read before evaluating)

- **Windows: unverified** (see above).
- **Lead-conversion concurrency** rests on an atomic conditional-update
  claim plus sequential tests; true parallel-process races were not
  load-verified.
- **Audit log is not tamper-proof** - anyone with database write access
  could alter it. It is an accountability feature, not a forensic one.
- **Reminders are in-app only** (computed on page load; no scheduler, no
  email/SMS).
- **No public registration** by design; Administrators create accounts.
- No external penetration test or full WCAG audit has been performed.
 


## Documentation in `docs/`

| File                         | Contents                                                                   |
|----------------------------- |----------------------------------------------------------------------------|
| `REQUIREMENTS_CHECKLIST.md`  | Assignment requirements → implementation → test evidence; unverified items |
| `DEMO_GUIDE.md`              | Scripted demo walkthrough for every module                                 |
| `AI_USAGE.md`                | Truthful summary of how AI assistance was used                             |
| `SECURITY_REVIEW.md`         | Phase 7 security/backend audit and fixes                                   |
| `PRIVACY_REVIEW.md`          | Phase 8 privacy/data-handling audit                                        |
| `UX_ACCESSIBILITY_REVIEW.md` | Phase 9 responsive/accessibility audit (rendered-browser verified)         |
| `ASSET_LICENSE_REVIEW.md`    | Phase 10 licensing/attribution audit                                       |
| `THIRD_PARTY_LICENSES.md`    | Third-party copyright & license notices                                    |
| `PREMIUM_UI_POLISH.md`       | Phase 11 visual-polish plan and verification                               |
| `CODE_QUALITY_REVIEW.md`     | Phase 12 maintainability audit                                             |
| `RELEASE_HYGIENE_REVIEW.md`  | Phase 13 distribution-safety audit                                         |
| `FINAL_HANDOVER_REPORT.md`   | Phase 14 final verification and packaging report                           |

The root `AGENTS.md` / `CLAUDE.md` files ship with the Laravel 13 skeleton
(Boost guidelines) and are retained as part of the project's provenance.

## License

Project code is provided for the WebNet Pakistan assignment. Framework and
third-party components remain under their own licenses - see
`docs/THIRD_PARTY_LICENSES.md`.
