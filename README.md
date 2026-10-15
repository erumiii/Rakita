# Library Management System

Small library app: employees manage books, members, borrowing, and returns; members browse the catalog and see their own borrowings. Laravel 12 + PostgreSQL 16 with row-level security enforced in the database.

## Stack

- Laravel 12, Vite, Tailwind
- PostgreSQL 16 (Docker), two DB roles: `library_postgres` (superuser, migrations/tests) and `library_app` (runtime, RLS applies)

## Quick start (Docker)

```bash
docker compose up --build   # app on http://localhost:8000, Postgres on localhost:5433
```

First boot builds the images, waits for Postgres, runs migrations, seeds once
(employee `library` / `password` + sample books), then starts nginx (port 8000,
proxying to the PHP-FPM app container) and the scheduler container
(`schedule:work`). Restarts are safe: seeding only happens while the `users`
table is empty.

```bash
docker compose exec app php artisan test   # RLS suite (uses library_test)
docker compose logs -f nginx app scheduler
docker compose down                        # add -v to also wipe the DB volume
```

`public/phpinfo-check.php` is a local XAMPP diagnostic and is excluded from the
Docker image.

## Setup (host, without Docker)

```bash
docker compose up -d db     # Postgres 16 on port 5433 (creates library_app role + library_test DB)
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
- Max **3** pending reservations per member
- Reservation pickup window **3 days** once the copy is held (FIFO per book)

## Reservations

Members reserve books from the catalog (`/member/books`) and track their queue at `/member/reservations`; employees manage all reservations at `/employee/reservations`.

- Reserving an **available** book with an empty queue → pickup offer immediately (`expires_at` = now + 3 days); a live offer blocks borrowing by other members.
- Reserving a **borrowed** book → join the FIFO queue (`expires_at` NULL).
- On **return**, the queue head gets the offer; on **pickup** (employee records the borrow) the reservation becomes `fulfilled`.
- Scheduled command `reservations:expire` (every minute) expires stale offers and promotes the next in line — it also covers member-side cancellations, since member RLS may not touch other members' rows.
- RLS: members only see/cancel their own rows; employees see all.

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
