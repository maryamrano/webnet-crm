
# AI Usage & Development Methodology

*Transparent disclosure of development tools used in this project, in compliance with assignment guidelines.*

## Overview

This project was built using a hybrid engineering workflow. **Maryam Fatima** acted as the System Architect and Lead Developer, driving all technical decisions, business logic, and code review. **Muse Spark (Meta)** was utilized as an advanced AI-assisted coding tool and pair programmer to accelerate implementation, boilerplate generation, and test scaffolding under strict developer supervision.

## The Developer's Role (Architecture, Logic & Oversight)

As the lead engineer, the developer retained full ownership and control over the software lifecycle:
- **System Architecture & Tech Stack:** Enforced strict technical constraints (Laravel 13, PHP 8.4, single-organization scope, and SQLite target verification).
- **Domain Modeling & Business Logic:** Designed the core security and data models, including the Admin/Manager/Sales Executive permission matrix, Sales Executive data scoping, deal-probability override rules, and report metric definitions.
- **Code Review & Quality Control:** Every component, migration, and test generated or assisted by the AI tooling was reviewed, analyzed, and validated line-by-line by the developer before integration.
- **Gated Lifecycle Management:** Structured development into 14 distinct phases-manually overseeing security, privacy, accessibility audits, and release hygiene.

## The AI Tooling's Role (Assisted Implementation)

The AI assistant served as a productivity multiplier for implementation tasks:
- Assisted in generating boilerplate code, repetitive controller logic, and database seeders based on explicit developer specifications.
- Scaffolding unit and feature test suites to match the developer's acceptance criteria.
- Assisting in drafting technical documentation and internal audit reports (`docs/`) under direct instructions.

## Verification & Quality Assurance

- **Test Suite:** All automated tests were executed in a Linux environment (Ubuntu, PHP 8.4.23, SQLite), resulting in **121 tests / 529 assertions passing** at handover, alongside verified database seeding.
- **Accessibility & UI Auditing:** Responsive and accessibility checks (Phase 9) were verified across six viewport widths using a headless Chromium browser against the running application.
- **Packaging & Delivery:** Final packaging (Phase 14) was rigorously tested by extracting the delivered artifact into a fresh directory, re-running installations, migrations, seeders, and full test suites.

## Project Limitations & Known Constraints

To prevent overstating the project scope:
- **Environment Scope:** Verified on both Linux and Windows environments using SQLite; MySQL remains an untested compatibility target.
- **Concurrency:** True parallel-process conversion races were not load-tested under heavy production traffic.
- **External Audits:** Internal audits (`docs/`) are scoped self-assessments; no external third-party penetration testing or formal WCAG certification was performed.

## Provenance

- The Laravel 13 framework skeleton (MIT, © Taylor Otwell) including standard framework vendor files (`AGENTS.md` / `CLAUDE.md`) has been retained unchanged to maintain accurate framework provenance.
- Full third-party licensing details are documented in `docs/THIRD_PARTY_LICENSES.md`.