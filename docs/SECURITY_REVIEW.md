# Security & Performance Review — Phase 7 (internal audit)

Scope: internal code/config review of this Laravel application plus automated
checks (`composer audit`, route/middleware inventory, targeted greps, query
count measurement). This is **not** an external penetration test and no
production environment was involved.

## Confirmed strengths (verified, not assumed)

- **Authentication**: Breeze session auth; login throttling present
  (`LoginRequest` rate limiter); public registration removed (404); passwords
  hashed by the framework; demo credentials exist only in seeders and are
  documented as demo-only.
- **Authorization / IDOR**: every CRM route sits behind `auth`; admin routes
  behind `role:admin` (verified via `route:list`). Per-entity Policies plus
  visibility-scoped queries; direct-URL access returns 403 — covered by tests
  for leads, companies, contacts, deals, tasks, activities, audit viewer and
  report exports.
- **Validation**: form requests on all write paths; relationship integrity
  validated server-side (contact↔company, deal↔visible company, whitelist
  parent types for tasks/activities).
- **CSRF**: all state-changing forms use `@csrf` inside the `web` middleware
  group; no CSRF exemptions defined.
- **SQL injection**: no raw SQL with user input anywhere; one static
  `orderByRaw` CASE expression with no interpolation. All queries use the
  query builder with bound parameters.
- **XSS**: Blade escaping everywhere; the single `{!! !!}` usage renders an
  internally generated badge built from a numeric delta and static strings.
- **Mass assignment**: models use explicit fillable lists; every controller
  writes from `validated()` data; `created_by`/authorship are set server-side
  (tamper attempts covered by tests). Note: `role` is mass-assignable on the
  User model, but the only write paths are the admin-validated user controller
  and factories — profile update rules expose only name/email.
- **File handling**: the application has no file upload functionality at all —
  no upload attack surface.
- **Secrets**: no hardcoded secrets found in code/config; `.env` is excluded
  from the handover ZIP; `APP_KEY` is generated per install.
- **Dependencies**: `composer audit` — **no known vulnerability advisories**.
  Production JS/CSS build is ~312 KB total (self-contained; Chart.js bundled
  locally since Phase 7 — no CDN dependency).
- **Audit log**: append-only at the application layer (no edit/delete routes;
  model guards refuse mutation), Admin-only viewer, no secrets in payloads,
  conversion entries are transactional with the conversion itself.
- **CSV exports**: same scoped builders as on-screen reports; formula
  injection guard for leading `= + - @` cells; exports audited without
  logging contents.

## Defects found in this phase and fixed

1. **Conversion state disclosure before authorization** — the convert
   endpoints revealed whether/when a lead was converted to any authenticated
   user who guessed its ID, before the permission check. Authorization now
   runs first; regression tests assert 403 + no date disclosure, while
   authorized users still get the friendly message.
2. **Team report query explosion** — ~630 queries on a factory-grown dataset
   (4 queries per team member). Rebuilt on grouped aggregates: ~30 queries
   total for the same page, identical numbers (existing report tests lock the
   outputs).
3. **Pipeline board N+1** — stage relation was lazy-loaded per deal
   (68 queries → 10 after eager loading).
4. **Missing indexes** on ownership/assignment columns used by every scope and
   report (`leads.assigned_to/created_by`, `deals.owner_id/created_by`,
   `tasks.assigned_to`, `tasks(status, due_at)`, `tasks.reminder_at`) — added
   in migration `2026_10_15_000010_add_performance_indexes`, existence locked
   by a schema test.
5. **`.env.example` shipped `APP_DEBUG=true`** — changed to `false` so a
   careless production copy does not leak stack traces by default.
6. **Dashboard charts depended on an external CDN** — Chart.js is now bundled
   into the local Vite build; the dashboard works fully offline.

## Measured query counts (seeded-style dataset: 40 leads, 30 deals, 35 tasks)

| Page | Before | After |
|---|---|---|
| Dashboard | 28 | 28 |
| Pipeline board | 68 | **10** |
| Conversion report | 9 | 9 |
| Pipeline report | 20 | 20 |
| Team report | 630 | **30** |
| Leads list | 24 | 24 |
| Tasks list | 28 | 28 |
| Reminders | 56 | 56 |
| Companies list | 36 | 36 |

All list pages paginate (12–20 per page). Reports intentionally load their
full scoped result sets into memory to compute weighted aggregates with the
single shared probability rule — appropriate at this application's scale;
documented here rather than hidden.

## Residual risks / unverified areas (not fixed, by design or scope)

- **Session cookie flags**: for a real production deployment set
  `SESSION_SECURE_COOKIE=true` (HTTPS) in `.env`; left unset for the local
  demo setup. `SESSION_ENCRYPT=false` is the framework default.
- **MySQL / Windows**: unverified (see REQUIREMENTS_CHECKLIST.md).
- **Parallel conversion races**: mitigated by design (atomic conditional
  claim), not verified under true multi-process load.
- **Audit log tamper-resistance**: application-level only; a database admin
  could alter entries. External immutable storage is out of scope.
- **`npm audit` could not run** in this environment (registry audit endpoint
  error). All npm packages are build-time dev tooling; the shipped artifact
  is the compiled `public/build` output.
- **Authorization is per-record policy based**; there is no IP allow-listing,
  2FA, or SSO — none were in the assignment scope.
