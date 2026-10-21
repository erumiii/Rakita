# Library Management System

Small library app: employees manage books, members, borrowing, and returns; members browse the catalog and see their own borrowings. Laravel 12 + PostgreSQL 16 with row-level security enforced in the database.

## Overview
Role	Pages	What they can do
| :---: | :---: | :---: |
| Role | Pages | What They Can Do |
| Employee | Buku, Borrow, Members, Reservation |Add/edit/delete books (search, filter, pagination); record borrowing and returning; manage members; see and cancel all reservations|
|Member |Koleksi Buku, Pinjaman Saya, My Reservations |Browse and search the catalog; reserve a book; see only their own current and past borrowings with due dates and reminders; track and cancel their own reservations |

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

## How to Run

Install Docker Desktop (or Docker Engine) with Compose v2, and Git. Make sure ports 8000 and 5433 are free.
Get the code: git clone <repo-url> then cd into the project folder.
Start everything:
bash
   docker compose up --build
First boot builds the images, waits for Postgres, runs migrations, and seeds the employee account and sample books once. Use docker compose up -d --build to run in the background. 4. Wait until healthy: run docker compose ps in another terminal; db, app, and nginx should show healthy. 5. Open http://localhost:8000 and log in as library / password (employee), or register a member at /register. 6. Stop with docker compose down (data is kept). docker compose down -v also deletes the database volume, so avoid it before a demo.

## Services, ports, and health checks

|---|---|---|---|---|
| Service |	Container	| Role	| Host port |	Health check |
| nginx |library-app-nginx | Reverse proxy, only web entry point |	8000 | GET /up |
| app |	library-app	| Laravel on PHP-FPM |	none (9000 internal) |	php-fpm -t |
| db |	library-app-db |	PostgreSQL 16, volume library_pgdata |	5433 |	pg_isready |
| scheduler	| library-app-scheduler	| schedule:work (reservation expiry, overdue flag) |	none	| none (serves no requests) |

## Logs

|---|---|---|
| Source |	What it shows	| How to view |
| nginx |	Every HTTP request (method, path, status code) and proxy errors |	docker compose logs nginx |
| app | PHP-FPM output and Laravel errors (e.g. database connection failures) |	docker compose logs app |
| db |	PostgreSQL startup, init script, and errors |	docker compose logs db |
| scheduler | Each scheduled run, e.g. reservations:expire and borrowings:mark-overdue|	docker compose logs scheduler |

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
- Reservation pickup window **1 day** once the copy is held; a book can be
  reserved by multiple members — the confirmation shows the queue position
  (first in line on an available book = queue #1)

## Reservations

Members reserve books from the catalog (`/member/books`) and track their queue at `/member/reservations`; employees manage all reservations at `/employee/reservations`.

- Reserving an **available** book with an empty queue → pickup offer immediately (`expires_at` = now + 1 day); a live offer blocks borrowing by other members.
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

## Manual Testing

1. Start: `docker compose up --build`, then `docker compose ps` — db, app, nginx must be `healthy`. '''bash Open http://localhost:8000/up (HTTP 200).'''
2. Login as `library` / `password`. Register two members at `/register` (e.g. `alice`, `bob`).
3. Employee: add a book, edit it, search/filter, delete an available book.
4. Employee → Borrow: lend a book to alice (7 days). Expect toast "Peminjaman dicatat", book becomes Borrowed. Record the return; book becomes Available.
5. Wrong input: add a book with a duplicate ISBN or empty page count → validation error, nothing saved. Borrow with period 8 → rejected.
6. Reservations: as bob reserve a Borrowed book → queue position shown; return the book → bob gets "Ready for pickup".
7. Isolation: alice sees only her borrowings/reservations; bob only his. A member opening `/employee/books` gets 403. Employee sees everything.
8. Persistence: `docker compose down` (no `-v`), `docker compose up -d`, log in — data is still there.
9. Failure + recovery: `docker stop library-app-db` → app shows an error; diagnose with `docker compose ps` and `docker compose logs app`; recover with `docker start library-app-db` — app works again, no data lost.
10. Automated RLS suite: `docker compose exec app php artisan test`.

x
