# Demo Guide — WebNet CRM

A scripted walkthrough for presenting the system. Everything below uses the
seeded fictional demo data; reset it any time with
`php artisan migrate:fresh --seed`.

## Before you start

1. Run the app: `php artisan serve`, open http://127.0.0.1:8000.
2. The login page carries a notice that this is a demonstration system with
   fictional sample data. Password for every demo account: **`password`**.
3. Suggested order: start as **Admin** for the full tour, then switch to the
   **Sales Executive** account to show data scoping — the single most
   important behaviour in the system.

## 1. Login & roles

- Log in as `admin@webnetcrm.test`. Note the **Administration** section in
  the sidebar (User Management, Audit Logs) — it exists only for Admin.
- Log out, log in as `manager@webnetcrm.test`: full CRM data, no
  Administration section.
- Log in as `sales@webnetcrm.test`: same screens, **fewer records** — the
  executive sees only leads/deals/tasks they own, are assigned to, or
  created. Also note there is **no public Register page** (try
  `/register` → 404): accounts are created by Administrators only.

## 2. Dashboard

- Six KPI cards (leads, companies, conversion rate, open deals, open
  pipeline value, won value) with month-over-month deltas — the definitions
  are printed at the bottom of the page (won value by month uses each
  deal's `closed_at`).
- Three charts: leads by status, a 6-month lead trend, pipeline value vs
  weighted value by stage. Charts work offline (Chart.js is bundled).
- Overdue and upcoming-task panels link straight into Tasks.
- Repeat as the Sales Executive: every number shrinks to their own records
  only — dashboard, lists and reports all use the same visibility rules.

## 3. Leads & conversion

- **Leads** list: filter by status, source, assignee and search text;
  12 per page.
- Create a lead whose email matches an existing lead → a **duplicate
  warning** appears. It is a warning only — nothing is merged or blocked.
  (As the Sales Executive, the warning only ever names records they are
  allowed to see.)
- Open a lead → **Convert**: choose an existing company/contact or create
  new ones, optionally open a deal. Convert → the lead shows as converted
  with links to the created records. Try converting again → the system
  refuses: conversion is a one-time, transactional operation guarded
  against double submission.

## 4. Companies & contacts

- **Companies** list with contact counts; open one to see its contacts,
  deals, tasks and activity timeline.
- Add a contact; mark it **primary** — exactly one primary per company is
  enforced when switching.
- Delete rules: deleting a company/contact is **Admin-only** and is
  blocked while related records exist (the UI explains why).

## 5. Deals & pipeline

- **Deals** list with stage, value and probability. Explain the rule:
  a deal's own probability wins if set; otherwise the **current stage's**
  probability applies (Won 100%, Lost 0%) — weighted values everywhere use
  that effective probability.
- **Pipeline board**: one column per stage; move a deal with the card's
  stage selector and watch column totals and weighted values update.

## 6. Tasks & reminders

- **Tasks**: create one linked to a lead/deal, set priority, due date and
  a reminder time; complete and reopen it.
- **Reminders page** (`Tasks → Reminders`): three sections — overdue,
  due within 48 hours, reminder time reached. Be precise when presenting:
  reminders are **in-app only**, computed when the page loads; there is no
  email/SMS and no background scheduler (the page says so itself).

## 7. Reports & CSV exports

- **Reports** hub → Conversion (grouped by **source** and by **assignee**),
  Pipeline by stage, Team performance. Apply the date/search filters.
- **Export CSV** on any report: the file contains exactly the filtered
  on-screen data. Values that look like spreadsheet formulas are
  neutralised with a leading apostrophe.
- Definitions to state if asked: conversion-report "owner" = the lead's
  assignee; team-report overdue tasks are a snapshot of right now, not a
  period metric.

## 8. User administration (Admin only)

- **Admin → User Management**: create a user with a role, edit, reset a
  password, deactivate/delete — with safeguards (you cannot delete
  yourself; the last Admin cannot be removed).

## 9. Audit logs (Admin only)

- **Admin → Audit Logs**: every create/update/delete, conversion, stage
  change and CSV export is recorded with actor, time and changed fields
  (passwords are never recorded — only that a password changed).
- State the limitation honestly if asked: the log is an accountability
  feature at application level; it is **not tamper-proof** against someone
  with direct database access.

## If something looks empty

Fresh installs start from seed data, so screens are populated. If you
deleted records while exploring, `php artisan migrate:fresh --seed`
restores the original demo state (this also resets the audit log).
