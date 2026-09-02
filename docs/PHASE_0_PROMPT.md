**DATABASE OASIS**

**AI Coding Agent Prompt - Phase 0**

Internal Sales Operations Database - Marison Regency

| **Target domain** | databaseoasis.marison.id                |
|-------------------|-----------------------------------------|
| **Platform**      | Web app internal, desktop-first         |
| **Stack**         | Laravel + Filament + PostgreSQL         |
| **Dokumen**       | MVP-first, siap dipakai AI coding agent |

Versi 1.0 - September 2026

# Cara pakai

- Buat/pilih repository untuk Database Oasis.

- Upload dokumen Master Build Pack ke coding agent jika agent mendukung file context.

- Copy prompt di bawah sebagai instruksi pertama.

- Setelah Phase 0 selesai dan terverifikasi, jangan izinkan agent lanjut otomatis.

# Copy-Paste Prompt

```text
You are implementing Phase 0 of Database Oasis.
PROJECT CONTEXT
Database Oasis is a separate internal sales operations application for Marison Regency. It will eventually manage Consumer -> BI Checking -> PSJB -> Document Submission -> Bank Process -> Developer PPJB -> Akad -> BAST. It is NOT Oasis CRM and it must NOT use Google Sheets as its database.
TECH STACK (LOCKED)
- PHP 8.4+
- Laravel
- Filament
- PostgreSQL
- Docker for local development
- PHPUnit or Pest
- Laravel Pint
- PHPStan/Larastan
PHASE 0 GOAL
Create a clean, reproducible, tested project foundation only. Do not implement business-domain CRUD yet.
IMPLEMENT
1. Bootstrap Laravel application with a clear README.
2. Configure PostgreSQL and a Docker-based local development setup.
3. Install/configure Filament and ensure the admin panel loads.
4. Implement authentication baseline: login/logout and an is_active user guard.
5. Create RBAC foundation suitable for Super Admin, HQ Admin, Branch Admin, Branch Manager, Management, Auditor. It is acceptable to seed only Super Admin in Phase 0; full branch scoping comes later.
6. Configure ULID-ready model conventions for future domain models.
7. Add code quality tools: Pint and PHPStan/Larastan.
8. Add automated test framework and basic smoke/auth tests.
9. Add CI that runs install, lint, static analysis, and tests.
10. Add health endpoint/check and baseline structured logging.
11. Provide .env.example with no secrets.
12. Ensure migrations are reversible and production-safe.
DO NOT IMPLEMENT YET
- branches
- projects
- units
- consumers
- sales_cases
- BI/PSJB/bank/PPJB/akad/BAST
- Google Sheets sync
- migration engine
- dashboards
ENGINEERING RULES
- Prefer the simplest maintainable solution.
- Do not create a custom UI design system; use Filament defaults.
- Do not hide failing tests or reduce static analysis to get green CI.
- Do not add unrelated packages.
- Do not commit credentials.
- Document every setup command.
VERIFY
Run and report:
- dependency install
- database migrations
- test suite
- Pint
- PHPStan/Larastan
- application startup / health endpoint
- Filament login smoke check
FINAL RESPONSE FORMAT
1. Phase 0 status: PASS/FAIL
2. What was implemented
3. Key technical decisions
4. Files changed/created
5. Exact verification commands and results
6. Known issues / risks
7. Recommended next step
STOP after Phase 0. Do not start Phase 1 until explicitly approved.
```
