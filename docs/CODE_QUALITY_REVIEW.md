# Code Quality, Maintainability & Technical-Debt Review — Phase 12

Method: full-tree inspection of controllers, services, policies, requests,
models, routes, views, JS/CSS, migrations, seeders and tests; Laravel Pint
in check mode (114 files); targeted greps for duplication, dead code and
debug residue; query counts re-measured on detail pages. Every remediation
below was verified by the test suite (121 tests) after the change.

## Findings & remediation

### Fixed in this phase

**Q1 (Medium) The parent-type whitelist existed in two copies.**
`TaskController` and `ActivityController` each carried an identical private
`$parentTypes` map (lead/company/contact/deal) that had to be kept in sync
by hand — exactly the kind of duplication that drifts and opens an
authorization hole when one copy is edited.
*Fix:* single source of truth `App\Support\CrmParentTypes` (map + key/class
lookups); both controllers now use it. Tasks/Activities suites pass
unchanged.

**Q2 (Medium) The "who may be assigned" rule existed in three copies.**
`LeadController::assignableUsers()` and `CompanyController::assignableUsers()`
were byte-identical private methods, and `DealController::formData()` inlined
the same ternary a third time.
*Fix:* one home for the rule — `User::assignableFor($actor)` (Sales
Executive → only themselves; Admin/Manager → everyone, name-ordered). All
three call sites use it; behavior locked by the existing form/assignment
tests.

**Q3 (Low) Dead Breeze components shipped in the tree.**
`resources/views/layouts/navigation.blade.php` (unused top-nav — the app
uses its own sidebar layout) and
`resources/views/components/application-logo.blade.php` (orphaned when the
guest layout adopted the product wordmark in Phase 10) had **zero
references** anywhere (verified by search).
*Fix:* both files deleted. (The stock `welcome.blade.php` remains: it is
still the routed `/` page — replacing it is a product decision for the
final phase, not dead code.)

**Q4 (Low) An unused build dependency.**
`@tailwindcss/vite` was declared and installed but never imported: the CSS
pipeline is PostCSS + Tailwind v3 (`postcss.config.js`, `tailwind.config.js`
+ `@tailwindcss/forms`, which *is* used). Verified with `npm ls` (top-level
only, no dependents).
*Fix:* `npm uninstall @tailwindcss/vite`; `npm run build` re-verified and a
rendered dashboard check at 390/360/320 px re-run clean (0 overflow). The
pre-change `package.json`/lock were backed up before removal.

**Q5 (Low) Style drift in authored code.**
Pint reported 21 style issues project-wide, including genuinely unused
imports in `ContactFormRequest` and `LeadFormRequest`.
*Fix:* `pint app/` applied (import ordering, FQCN style, unused imports).
Suite green afterwards. The 8 remaining Pint notices are in tests,
factories and `bootstrap/app.php` (skeleton/test code) — left untouched;
they have no functional effect.

### Reviewed and deliberately NOT changed

- **Filter code in index controllers** repeats a `$request->only(...)` +
  `when()` idiom per module. Each filter set differs (fields, relations,
  scoping); abstracting it would add a framework of our own for no measured
  benefit. Accepted Laravel idiom.
- **`ContactController::duplicateWarning` vs `LeadDuplicateFinder`** look
  similar but answer different questions (contacts-only, message string vs
  structured multi-entity warnings). Merging them would blur the Phase 8
  privacy semantics for no defect. Left separate, both visibility-scoped.
- **Detail-page query counts** re-measured: lead show 8, company 14,
  contact 15, conversion 15, deal 28 queries. Deal show is the highest and
  was inspected line by line — every relation is eager-loaded
  (`tasks.assignee`, `activities.author`, stage/owner/company/contact/lead);
  the count is relation fan-out + layout, not an N+1, and does not grow per
  record. No change.
- **Email-verification routes/controllers** (Breeze) remain registered.
  Users are created pre-verified by Admins, so the flow is unused in
  practice, but it is functional framework surface; removing routes was
  out of bounds for this phase ("preserve routes"). Noted as skeleton
  surface area.
- **Dev-only skeleton tooling** (`laravel/pail`, `laravel/pao`, Pint,
  Collision, Faker, PHPUnit) stays: standard Laravel dev dependencies,
  never shipped in the production artifact.
- **README.md is still the stock Laravel README** — the one contradictory
  document in the tree. Rewriting it is explicitly the final documentation
  phase's job; flagged here so it cannot be forgotten.

### Verified clean during the audit
- No debug residue (`dd`, `dump`, `var_dump`, `console.log`, TODO/FIXME)
  in application code, routes, views or JS.
- No hardcoded hosts/paths/environment assumptions in `app/` — config goes
  through `config()`/`env()` in the standard Laravel way.
- Migrations all carry working `down()` methods (incl. the Phase 7 index
  migration and the Phase 5.5 audit table).
- Controllers stay thin where it matters: conversion logic lives in
  `LeadConversionService`, audit capture in `AuditLogger`, matching in
  `LeadDuplicateFinder`; policies remain the single authorization layer
  alongside `visibleTo` scopes.
- Test quality spot-check: the critical suites assert outcomes, not just
  status codes — e.g. conversion rollback asserts zero created records and
  zero audit events; CSV tests parse rows and assert scoped contents;
  privacy tests assert the *absence* of other users' names in responses;
  Phase 9 tests lock rendered accessibility markup.

## Tests & verification (actual)
- Focused: LeadManagement + CompaniesContacts + DealsPipeline — 23 passed
  after the dedupe (one intermediate mistake was caught here: two call
  sites initially referenced an out-of-scope `$request`; corrected to the
  original `auth()->user()` before proceeding).
- Full suite after all changes: **121 passed, 529 assertions** (identical
  count to the pre-audit baseline — no test weakened or removed).
- `php artisan migrate:fresh --seed` — clean.
- `npm run build` — clean; rendered dashboard re-checked at 390/360/320 px.

## Remaining technical debt (accepted, recorded)
1. Stock Laravel README (final documentation phase).
2. Stock `welcome.blade.php` landing page at `/` (final-phase decision).
3. 8 Pint style notices in tests/factories/bootstrap (cosmetic only).
4. Breeze email-verification surface unused by the admin-created-user model.
5. Known platform limitations carried from earlier phases (SQLite-only
   verification, no MySQL/Windows test, no true parallel-race test, audit
   log not tamper-proof) — unchanged by this phase and still stated in
   `docs/REQUIREMENTS_CHECKLIST.md`.
