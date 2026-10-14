# Library Management System

Small library app: employees manage books, members, borrowing, and returns; members browse the catalog and see their own borrowings. Laravel 12 + PostgreSQL 16 with row-level security enforced in the database.

## Stack

- Laravel 12, Vite, Tailwind
- PostgreSQL 16 (Docker), two DB roles: `library_postgres` (superuser, migrations/tests) and `library_app` (runtime, RLS applies)

## Setup

```bash
docker compose up -d        # Postgres 16 on port 5433 (creates library_app role + library_test DB)
composer install
npm install
cp .env.example .env        # sensible defaults already filled in
php artisan key:generate
php artisan migrate --seed  # creates the schema and seeds the employee account
npm run build
php artisan serve           # http://localhost:8000
```

## Seeded account

| Role | Username | Password | Notes |
|---|---|---|---|
| Employee | `library` | `password` | Single shared employee account for all staff tasks |

Members self-register at `/register`.

## URLs

- `/login`, `/register` — guest pages
- `/employee/*` — books CRUD, members, borrow/return, history (employee role only)
- `/member/*` — catalog + own borrowings (member role only)
- Cross-portal access (member hitting `/employee/*`, etc.) → 403

## Business rules

- Max **3** active borrows per member
- Borrow period **1–7 days**, due date = borrowed_at + period

## Overdue handling

Scheduled command `borrowings:mark-overdue` flags unreturned past-due borrowings. Run the scheduler every minute, e.g. cron:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Without the scheduler running, overdue status still shows in the UI: the member borrowing list calls `borrow_reminder()` on each due date, so late items are flagged on view.

## Tests

```bash
php artisan test
```

Requires the Docker DB running; tests use the `library_test` database (created by `docker/create_db.sql` on first container start). Each RLS test runs `migrate:fresh` + reseed per test — expect a slower suite.

## RLS (row-level security)

Runtime DB traffic runs as the limited `library_app` role. `FORCE ROW LEVEL SECURITY` is enabled on `borrowings` and `users`; books are a shared catalog with no RLS. Per-request context (`app.current_user_id`, `app.current_role`) is set via `set_config(..., false)` on the `pgsql_app` connection, so members only see their own rows. The superuser (`library_postgres`) bypasses RLS and is reserved for migrations, seeding, tests, and the overdue batch job.
