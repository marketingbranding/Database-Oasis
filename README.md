# Database Oasis

Internal sales operations application for Marison Regency. Phase 0 provides the Laravel/Filament/PostgreSQL foundation. Phase 1 adds branch-isolated master data. Phase 2 adds consumers and sales cases. Phase 3 adds BI checking and PSJB. Phase 4 adds document submissions, bank responses, SP3K, and explicit CASH advancement. Phase 5 completes both KPR and CASH chains through Developer PPJB, Akad, BAST, and Sales Case completion. Phase 6 adds the Sales Case workspace: header summary, process stepper, unified timeline, quick actions, case notes, and document-number-aware global search.

## Requirements

- Docker Desktop with Docker Compose, or PHP 8.4+, Composer 2, Node.js 22, and PostgreSQL 17

## Docker setup

```sh
cp .env.example .env
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose up -d
```

Admin login: `http://127.0.0.1:8000/admin/login`. Health check: `http://127.0.0.1:8000/up`.

Create first administrator after containers start:

```sh
docker compose exec app php artisan make:filament-user
docker compose exec app php artisan tinker --execute 'App\Models\User::where("email", "admin@example.com")->firstOrFail()->assignRole(App\UserRole::SuperAdmin);'
```

Stop services:

```sh
docker compose down
```

The following command permanently deletes local PostgreSQL data. Run it only when reset is intended:

```sh
docker compose down -v
```

## Native setup

```sh
cp .env.example .env
composer install
npm ci
npm run build
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Set `DB_HOST=127.0.0.1` in `.env` when PostgreSQL runs directly on host instead of Compose.

## Verification

Native PHP 8.4 environment:

```sh
composer install
npm ci
npm run build
composer audit --locked --no-interaction
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan migrate --seed
php artisan test --compact
vendor/bin/pint --format agent
composer analyse
php artisan serve
curl --fail http://127.0.0.1:8000/up
curl --fail http://127.0.0.1:8000/admin/login
```

Docker PHP 8.4 environment:

```sh
docker compose run --rm app composer install
docker compose run --rm app php artisan migrate:fresh --seed
docker compose run --rm app php artisan test --compact
docker compose run --rm app vendor/bin/pint --format agent
docker compose run --rm app composer analyse
docker compose up -d
docker compose ps
```

Production dependencies only:

```sh
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

## Logging

Default environment writes JSON-formatted Laravel logs to stderr. Configure `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, and `LOG_STDERR_FORMATTER` per environment.

## Phase boundary

Phase 5 contains `developer_ppjbs`, `akad_records`, and `bast_records`. One partial unique index protects the ACTIVE PPJB per Sales Case; unique constraints protect one Akad and one BAST per normal Sales Case. KPR PPJB resolves the authoritative approval server-side; CASH PPJB requires explicit CASH advancement and keeps `bank_process_id` null. Akad moves the Unit to TERJUAL while keeping the case ACTIVE. BAST closes the case as COMPLETED. Post-Akad close/move/PPJB correction workflows are blocked server-side. Document numbers remain warning-only business attributes and never relational keys.

Phase 5 does not contain Monitoring/KPI, Akad readiness/kendala, legacy migration, Google Sheets sync, document generation, attachments, or advanced Sales Case workspace/timeline UI.

Phase 6 is the presentation/orchestration layer on top of Phases 0-5. It adds: a Sales Case workspace with header summary, process stepper, and unified timeline; case-scoped append-only operational notes; document-number-aware global search that resolves to (possibly multiple) Sales Cases; Consumer and Unit case-history relation managers; and a centralized `SalesCase::daysInCurrentStage()` aging calculation. All quick actions still call existing Phase 2-5 domain actions; no domain rules are duplicated in UI code.

Phase 6 does not contain KPI/Monitoring, Akad readiness/kendala, legacy migration, Google Sheets sync, or document generation.

Phase 7 is the read/query monitoring layer: Akad targets, Akad realization with locked M1–M4 buckets, SP3K stock/aging, Akad readiness snapshots, kendala categories, and BAST monthly metrics. All KPIs derive through `MonitoringService` and are documented in `docs/MONITORING_DEFINITIONS.md`. Monitoring never mutates approved transactional workflow.

Phase 7 does not contain legacy migration engine, Google Sheets sync, Oasis CRM integration, attachments, document generation, notification automation, or advanced BI.
