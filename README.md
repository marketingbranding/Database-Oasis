# Database Oasis

Internal sales operations application for Marison Regency. Phase 0 provides Laravel, Filament, PostgreSQL, authentication, RBAC foundation, Docker, CI, health checks, and structured logging. Phase 1 adds master data with branch isolation. Phase 2 adds consumers and sales cases. Phase 3 adds BI checking and PSJB. Phase 4 adds document submissions, append-only bank responses, authoritative SP3K approval, and an explicit CASH path without fake bank/SP3K records.

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

Phase 4 contains `document_submissions` and `bank_processes`. Submission sequence is transactionally assigned per Sales Case and structurally unique. Bank responses are append-only; one partial unique index protects the single authoritative approval per Sales Case. APPROVED requires SP3K data and advances to PPJB_DEV. REJECTED preserves case/unit/history. CASH advances explicitly to PPJB_DEV after an ACTIVE PSJB and creates no submission, bank process, bank, or SP3K placeholder. PSJB cancellation is blocked once its actual downstream submission exists.

Phase 4 does not contain developer PPJB records, akad, BAST, monitoring, Google Sheets sync, or the legacy import engine.
