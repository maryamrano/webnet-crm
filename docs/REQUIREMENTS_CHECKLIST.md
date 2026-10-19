# Requirements → Implementation Checklist

WebNet CRM — Laravel 13 / PHP 8.4 / SQLite (development-verified). Status as of
Phase 9: **121 automated tests passing (529 assertions)**,
`php artisan migrate:fresh --seed` clean. Security review: `docs/SECURITY_REVIEW.md`;
privacy review: `docs/PRIVACY_REVIEW.md`; responsive/UX/accessibility review:
`docs/UX_ACCESSIBILITY_REVIEW.md` (rendering verified in headless Chromium at
1440/1280/768/390/360/320 px — 0 overflowing pages, 0 unlabelled controls,
one h1 per page); licensing review: `docs/ASSET_LICENSE_REVIEW.md` +
`docs/THIRD_PARTY_LICENSES.md`; visual polish: `docs/PREMIUM_UI_POLISH.md`
(Phase 11 — presentation-only; Phase 9 rendering matrix re-verified after
polish at 0 overflow / 0 unlabelled controls / one h1 per page); code-quality
review: `docs/CODE_QUALITY_REVIEW.md` (deduplicated parent-type whitelist and
assignment rule, dead components and one unused dependency removed, Pint
applied to `app/` — 121 tests still passing); release hygiene:
`docs/RELEASE_HYGIENE_REVIEW.md` (file-by-file disposition, generated
artifacts purged + regeneration proven, handover checklist); **Phase 14
(final): README, DEMO_GUIDE and AI_USAGE written; final ZIP built and
verified by fresh extraction — see `docs/FINAL_HANDOVER_REPORT.md`**.

**Final QA update (2026-10-19):** suite **121 passed (530 assertions)** after two
surgical changes — (1) the dashboard chart initialisation now runs on
`DOMContentLoaded` (the Vite bundle is a deferred module script, so the inline
init previously ran before `window.Chart` existed and silently skipped; all
three charts browser-verified drawing for Admin and Sales Executive), and
(2) the root route now redirects to `/login`, adopting the project owner's
Windows-copy behaviour (no public stock welcome page). `/profile` content and
functionality browser-verified (information update, password form, delete form,
server-side validation). Full detail: `docs/FINAL_QA_REPORT.md`.

## Assignment requirements

| # | Requirement | Implementation | Verified by |
|---|---|---|---|
| 1 | Dashboard with CRM statistics, performance summaries, upcoming tasks & reminders | `DashboardController` — scoped totals, conversion rate, open/won pipeline values, month-vs-month comparisons, 3 Chart.js charts, upcoming-7-day + overdue task panels, empty states | DashboardReportsTest |
| 2 | Lead management: team assignment, sources, statuses, lead conversion | Leads CRUD + filters/pagination; sources & statuses as model constants; conversion flow with explicit duplicate choices | LeadManagementTest, LeadConversionTest |
| 3 | Duplicate prevention during conversion | Normalized matching (email exact, phone normalized, company name candidate), explicit user confirmation, atomic transaction with conditional claim | LeadConversionTest (T-C1/T-C2/rollback) |
| 4 | Customer/company profiles, contacts, communication history, related records | Companies + contacts CRUD, inherited visibility, related deals/leads on the company page, activities timeline on lead/company/contact/deal pages | CompaniesContactsTest, ActivitiesTest |
| 5 | Sales pipeline: stages, deal values, expected closing dates, progress | Pipeline board + list + report; seeded stages; effective probability rule (override → stage; Won 100 / Lost 0) | DealsPipelineTest |
| 6 | Tasks, assignments, deadlines, reminders, follow-up history | Tasks CRUD, polymorphic parents (whitelist), complete/reopen timestamps, in-app reminders page with documented deterministic rules (no scheduler/email) | TasksTest |
| 7 | Authentication + Administrator / Manager / Sales Executive roles, server-side authorization | Breeze auth (public registration removed), role column + middleware, per-entity Policies + scoped queries, direct-URL 403 tests for every entity | AdminUserManagementTest + per-module tests |
| 8 | Reports: lead conversion, pipeline summaries, team performance, with filters | Three reports + shared filter logic; definitions documented on each page | DashboardReportsTest |
| 9 | CSV report exports | All three reports exportable; same builders as on-screen data; formula-injection guard; audited | DashboardReportsTest |
| 10 | Responsive, professional UI | Blade + Tailwind, dark sidebar / indigo design, mobile drawer, responsive tables/boards | Phase 9 rendering audit in headless Chromium at 6 widths × 15 screens + UxAccessibilityTest (see UX_ACCESSIBILITY_REVIEW.md) |
| 11 | Sound relational modelling, validation, error handling, security | FKs with deliberate restrict/null-on-delete, normalized match columns, form-request validation everywhere, audit trail (Phase 5.5) | CrmSchemaTest, AuditTrailTest |
| 12 | Sample data + reproducible database setup | Fictional seeders (6 stages, 8 companies, 12 contacts, 20 leads, 12 deals, 15 tasks, 10 activities, 3 demo users) | migrate:fresh --seed (verified repeatedly) |
| 13 | Automated tests for critical workflows and permissions | 121 tests across auth, admin, schema, leads, conversion, companies, contacts, activities, audit, deals, pipeline, tasks, dashboard, reports, hardening | Full suite |

## Cross-cutting approved rules

- Probability: deal override first, else **current** stage probability; weighted values use the effective probability everywhere (board, list, dashboard, reports) — locked Phase 5/6.
- Metric definitions (locked Phase 6): won-value month comparison by `closed_at`; team overdue = current snapshot; conversion "owner" = lead assignee.
- Audit trail (Phase 5.5): explicit capture, field whitelists, no secrets, Admin-only viewer, application-level append-only (not tamper-proof).
- Duplicate matching respects visibility scopes (Phase 8): warnings and conversion candidates only ever involve records the acting user may see; Managers/Admins match organization-wide as before.
- No public registration; users are created by Administrators only.
- In-app reminders only; no email/SMS; no background scheduler claimed or implied.

## Explicitly NOT verified (honest limits)

- **MySQL compatibility** — migrations use portable Schema-builder constructs, but the app has only ever run on SQLite in this environment.
- **Windows execution** — developed/tested in a Linux environment. The project owner has since run the app on Windows (it works; the dashboard charts needed the 2026-10-19 `DOMContentLoaded` fix, now in the source and in the final ZIP). A formal step-by-step Windows acceptance walkthrough has not been recorded; the README's Windows steps remain the procedure to follow.
- **True parallel-process conversion races** — the conversion claim is an atomic conditional update (database-appropriate), covered by tests for repeat/sequential attempts; genuine multi-process concurrency has not been independently verified.
- **External penetration test / production deployment** — not performed; see `docs/SECURITY_REVIEW.md` for the internal audit scope.
