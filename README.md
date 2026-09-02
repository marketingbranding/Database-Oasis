# Database Oasis

Internal sales operations application for Marison Regency. Phase 0 provides Laravel, Filament, PostgreSQL, authentication, RBAC foundation, Docker, CI, health checks, and structured logging. Phase 1 adds master data (branches, projects, units, banks, users) with branch isolation. Phase 2 adds the transactional foundation: consumers and sales cases with domain actions (create, mundur, reject, cancel, pindah kavling) and structural one-ACTIVE-case-per-unit/consumer guards. Phase 3 adds BI checking and PSJB records attached to sales cases, with domain actions (record BI check, create/reissue/cancel PSJB), centralized stage transitions, and a structural one-ACTIVE-PSJB-per-case guard.

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

Phase 3 contains the first transactional stages: `bi_checks` (append-only history, `BiCheck::latestForCase` as the central current-result query) and `psjbs` (ACTIVE/SUPERSEDED/CANCELLED lifecycle, partial unique index enforcing one ACTIVE PSJB per case). Stage transitions are centralized: `SalesCaseStage::order()/isBeyond()` plus `SalesCase::advanceStageTo()`; BI CLEAR → PSJB, ACTIVE PSJB → PEMBERKASAN, no accidental regression beyond the PSJB stage, deliberate regression only via PSJB cancellation with a downstream guard.

Phase 3 does not contain pemberkasan (document submissions), bank processes, SP3K, CASH downstream logic, PPJB developer, akad, BAST, monitoring, Google Sheets sync, or the legacy import engine.
