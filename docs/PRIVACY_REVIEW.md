# Privacy & Data-Handling Review — Phase 8 (internal audit)

Scope: internal review of this demonstration CRM's data handling against
reasonable privacy expectations. **This document is not legal advice, and the
application is not certified compliant with any jurisdiction's law.**
Jurisdiction-specific obligations (e.g. Pakistan's PECA 2016 and draft
Personal Data Protection Bill, or the GDPR where EU data subjects are
involved) require review by the deploying organization.

## Data inventory

| Data store | Personal / business data held | Who can see it |
|---|---|---|
| `users` | Name, work email, password hash, role, remember-me token, email-verified timestamp | Admin (user management); names appear in assignee/owner pickers for all users |
| `leads` | Contact name, email, phone, company name (free text), source, status, notes (free text), assignment | Admin/Manager: all. Sales Executive: own (assigned/created) only |
| `companies` | Company name, industry, website, address, phone, email, ownership | Same visibility rule as leads |
| `contacts` | Name, designation, email, phone, primary flag | Inherited from parent company |
| `deals` | Title, value (PKR), closing dates, linked company/contact/lead | Same visibility rule |
| `tasks` | Title, description (free text), assignee, due/reminder dates | Same visibility rule |
| `activities` | Type, subject, body (free text), author, timestamp | Follows parent record visibility; edit/delete: author/Manager/Admin |
| `audit_logs` | Actor id + **actor name snapshot**, action, before/after values of whitelisted business fields (includes lead/contact names, emails, phones), export/report metadata (names of reports + filter keys only) | **Administrators only** |
| `sessions` | Session payloads incl. authenticated user id (database driver) | Server-side only |
| `password_reset_tokens` | Email + framework-managed token, expiry by use/time | Server-side only |
| CSV exports (files, once downloaded) | Report rows: names of sources/owners/companies, counts, monetary values | Generated only from the requester's visible scope; each generation is audit-logged |

No analytics, trackers, third-party data processors, email/SMS sending, or
file uploads exist in the application. The default mail configuration writes
to the local log file (`MAIL_MAILER=log`) — password-reset/verification mail
is not delivered externally in the demo setup.

## Data-flow summary

Data enters only through authenticated forms (validated server-side),
is displayed only through visibility-scoped queries and policies, leaves via
three audited CSV exports, and is retained in the single application
database. User deletion nullifies ownership/authorship references (records
and history survive, per the approved design); audit entries keep a name
snapshot of the actor so history stays attributable.

## Confirmed defects — found and FIXED in this phase

### D1 (Medium) Duplicate matching leaked records across visibility scopes
Lead duplicate warnings, conversion candidates, and the contact-create
duplicate warning searched the **entire database** and displayed names
(e.g. "Contact Zoya Hidden (Beta Secret Co)") to any user whose record
happened to match — including Sales Executives who are forbidden from
opening those records. A Sales Executive could also link their lead, during
conversion, to a company/contact they cannot access.

*Fix:* all matching in `LeadDuplicateFinder` and the contact duplicate
warning is now scoped by the acting user's `visibleTo` visibility. Managers
and Admins (organization-wide visibility) see exactly what they saw before;
Sales Executives now only match against records they may open.
*Tests:* 4 new regression tests (`PrivacyScopingTest`) covering the lead
page, the conversion screen + forced-post rejection, the contact warning,
and a Manager positive control. Six older test fixtures were aligned to the
corrected semantics (candidate records owned by the acting user).

### D2 (Low) No warning against entering real data into the demo
Nothing in the product told a user that the seeded deployment is a
demonstration with fictional data.

*Fix (partial):* the login page now carries a visible notice —
"Demonstration system with fictional sample data. Please do not enter real
customer or personal data into an unapproved deployment." Full warnings in
the README/handover documentation remain scheduled for the final
documentation phase.

## Recommendations (not defects; no code changed)

- **R1 Retention:** there is no automatic retention or purge anywhere.
  Audit logs, sessions (beyond natural expiry) and soft business history are
  kept indefinitely. The deploying organization should set a retention
  policy appropriate to its obligations; a purge command can be added later
  if required.
- **R2 Directory exposure (informational):** assignee/owner dropdowns and
  report filters list all team members' names to every user. Normal for an
  internal single-organization CRM, noted for completeness.
- **R3 Related-record names:** a visible record shows names of its related
  records (e.g. a deal's originating lead) even when that related record is
  outside the viewer's own scope — the relationship itself is part of the
  visible record; the related record's page still returns 403. Accepted as
  designed; revisit if stricter compartmentalization is ever required.
- **R4 Free-text fields:** notes, task descriptions and activity bodies can
  contain anything a user types. Content discipline is an organizational
  control; the application intentionally does not censor business notes.
- **R5 Downloaded CSVs** contain personal/business data by design. Handling
  after download (storage, sharing, deletion) is an organizational control;
  generation is scoped and audited (Phase 6/7).
- **R6 Session invalidation:** deleting a user removes their ability to log
  in (the account row is gone); any pre-existing session rows age out with
  the session lifetime rather than being force-purged. Standard framework
  behaviour; noted for a production hardening list.
- **R7 Audit-log content:** before/after values include business contact
  details (emails/phones) because accountability requires them; access is
  Admin-only and free-text bodies/notes and all secrets are excluded by
  whitelist (Phase 5.5, test-scanned).

## Items requiring legal / deployment-specific review

- **L1 Privacy notice & terms:** the application ships **no** privacy notice,
  terms of use, or consent text (verified by full-text scan). Any real
  deployment must add notices appropriate to its jurisdiction and data
  subjects. Nothing in this project should be read as a legal assurance.
- **L2 Lawful basis & data-subject rights:** access, correction and erasure
  workflows exist at the CRM-record level for authorized staff, but a formal
  data-subject-request process (identity verification, response timelines,
  records of processing) is an organizational/legal matter, not implemented
  or claimed here.
- **L3 Actor-name retention in audit logs** after account deletion is a
  deliberate accountability design (approved Phase 5.5); whether it aligns
  with a given erasure policy is a legal-review question.
- **L4 Hosting & backups:** database backups, their encryption, retention
  and jurisdiction are deployment decisions not present in this codebase.
- **L5 Demo credentials** (`admin@webnetcrm.test` etc., password `password`)
  exist only in seeders, are documented as demo-only, and must never be used
  in a real deployment. The AI-usage summary required by the assignment is
  preserved as a final-phase deliverable and has not been written yet;
  nothing in the codebase claims human authorship of AI-assisted parts.

## Tests

- New: `PrivacyScopingTest` — 4 tests, 16 assertions (leak closed on all
  three surfaces; Manager unaffected).
- Full suite after fixes: **118 passed, 497 assertions** (baseline entering
  this phase: 114). `migrate:fresh --seed` clean.

## Limitations

- Review is by code/config inspection and automated tests in a Linux/SQLite
  development environment; no production deployment, MySQL instance, or
  Windows host was examined.
- No external privacy audit, penetration test, or legal review has been
  performed. Findings marked as recommendations or legal-review items are
  deliberately not represented as compliance determinations.
