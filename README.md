# Database Oasis

Internal sales operations application for Marison Regency. Phase 0 provides Laravel, Filament, PostgreSQL, authentication, RBAC foundation, Docker, CI, health checks, and structured logging. Phase 1 adds master data (branches, projects, units, banks, users) with branch isolation. Transaction CRUD (consumers, sales cases) begins in Phase 2.

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

Phase 1 contains master data only: branches, projects, units, banks, users, all six roles, and branch isolation via policies plus scoped resource queries. Branch Admin and Branch Manager are restricted to their own branch; Auditor is read-only; Management has no master data pages.

Phase 1 does not contain consumers, sales cases, transaction process records, migration engine, Sheets sync, or dashboards.
