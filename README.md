# Library Management System

Small library app: employees manage books, members, borrowing, and returns; members browse the catalog and see their own borrowings. Laravel 12 + PostgreSQL 16 with row-level security enforced in the database.

## Overview
Role	Pages	What they can do

| Role | Pages | What they can do |
|---|---|---|
| Employee | Buku, Borrow, Members, Reservations | Add/edit/delete books (search, filter, pagination); record borrowing and returning; manage members; see and cancel all reservations |
| Member | Koleksi Buku, Pinjaman Saya, My Reservations | Browse and search the catalog; reserve a book; see only their own current and past borrowings with due dates and reminders; track and cancel their own reservations |

## Stack

- Laravel 12, Vite, Tailwind
- PostgreSQL 16 (Docker), two DB roles: `library_postgres` (superuser, migrations/tests) and `library_app` (runtime, RLS applies)

## Services, ports, and health checks

| Service |	Container	| Role	| Host port |	Health check |
|---|---|---|---|---|
| nginx |library-app-nginx | Reverse proxy, only web entry point |	8000 | GET /up |
| app |	library-app	| Laravel on PHP-FPM |	none (9000 internal) |	php-fpm -t |
| db |	library-app-db |	PostgreSQL 16, volume library_pgdata |	5433 |	pg_isready |
| scheduler	| library-app-scheduler	| schedule:work (reservation expiry, overdue flag) |	none	| none (serves no requests) |

## Logs


| Source |	What it shows	| How to view |
|---|---|---|
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

## Rakita Installation Steps

1. Clone the repo

```bash
   git clone https://github.com/erumiii/Rakita.git
   cd Rakita
```

2. Create the `.env` file

```bash
   cp .env.example .env
```

3. Generate the app key

```bash
   echo "base64:$(openssl rand -base64 32)"
```

   Paste the output to `APP_KEY` inside the `.env` file.

4. Change `DB_HOST` to the database service name inside the `.env` file

```env
   DB_HOST=db
```

5. Start the services using Docker Compose

```bash
   docker compose up --build
```

   The first run builds the images, waits for PostgreSQL, runs the migrations, and seeds the sample data once. To run in the background, use `docker compose up -d --build`.

6. List the running containers and check their health

```bash
   docker compose ps
```

   `db`, `app`, and `nginx` should show `healthy`.

7. Access the web

   Open http://localhost:8000 in your browser (or `<host-ip>:8000` if the app runs on another machine).
   Health check endpoint: http://localhost:8000/up (HTTP 200).

8. Log in

   - Employee: username `library`, password `password` (seeded account)
   - Member: register a new account at http://localhost:8000/register

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

## Tests

```bash
php artisan test
```

Requires the Docker DB running; tests use the `library_test` database (created by `docker/create_db.sql` on first container start). Each RLS test runs `migrate:fresh` + reseed per test — expect a slower suite.

## Manual Testing

1. **Start the stack**
   Run `docker compose up --build`, then `docker compose ps`. `db`, `app`, and `nginx` must be `healthy`. Open http://localhost:8000/up (HTTP 200).

2. **Login**
   Log in as `library` / `password`. Register two members at `/register` (e.g. `alice`, `bob`).

3. **Books (employee)**
   Add a book, edit it, search/filter, and delete an available book.

4. **Borrow and return**
   Employee → Borrow: lend a book to `alice` (7 days). Expect the toast "Peminjaman dicatat" and the book becomes Borrowed. Record the return; the book becomes Available.

5. **Wrong input**
   Add a book with a duplicate ISBN or an empty page count: validation error, nothing saved. Borrow with a period of 8 days: rejected.

6. **Reservations**
   As `bob`, reserve a Borrowed book: the queue position is shown. Return the book: `bob` gets "Ready for pickup".

7. **Isolation**
   `alice` sees only her own borrowings and reservations, `bob` only his. A member opening `/employee/books` gets 403. The employee sees everything.

8. **Persistence**
   Run `docker compose down` (no `-v`), then `docker compose up -d` and log in: the data is still there.

9. **Failure and recovery**
   Run `docker stop library-app-db`: the app shows an error. Diagnose with `docker compose ps` and `docker compose logs app`. Recover with `docker start library-app-db`: the app works again and no data is lost.

10. **Automated RLS tests**
    Run `docker compose exec app php artisan test`.

## Project Structure
```
Rakita/
├── .dockerignore
├── .editorconfig
├── .env.example
├── .gitattributes
├── .gitignore
├── Dockerfile
├── README.md
├── artisan
├── composer.json
├── composer.lock
├── design.md
├── docker-compose.yml
├── package.json
├── package-lock.json
├── phpunit.xml
├── vite.config.js
│
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── ExpireReservations.php
│   │       ├── GenerateNotifications.php
│   │       └── MarkOverdue.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── Controller.php
│   │   │   ├── NotificationController.php
│   │   │   ├── Employee/
│   │   │   │   ├── BookController.php
│   │   │   │   ├── BorrowController.php
│   │   │   │   ├── HistoryController.php
│   │   │   │   ├── MemberController.php
│   │   │   │   ├── ReservationController.php
│   │   │   │   └── ReturnController.php
│   │   │   └── Member/
│   │   │       ├── BookController.php
│   │   │       ├── BorrowingController.php
│   │   │       └── ReservationController.php
│   │   ├── Middleware/
│   │   │   ├── EnsureRole.php
│   │   │   └── SetDbSession.php
│   │   └── Requests/
│   │       ├── LoginRequest.php
│   │       ├── RegisterRequest.php
│   │       ├── StoreBookRequest.php
│   │       ├── StoreBorrowingRequest.php
│   │       ├── StoreReturnRequest.php
│   │       ├── UpdateBookRequest.php
│   │       └── UpdateMemberRequest.php
│   ├── Models/
│   │   ├── Book.php
│   │   ├── Borrowing.php
│   │   ├── Notification.php
│   │   ├── Reservation.php
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Services/
│   │   ├── BorrowingService.php
│   │   ├── NotificationService.php
│   │   └── ReservationService.php
│   └── Support/
│       ├── DbSession.php
│       └── helpers.php
│
├── bootstrap/
│   ├── app.php
│   ├── providers.php
│   └── cache/
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
│
├── database/
│   ├── edit-rules-old.php
│   ├── factories/
│   │   ├── BookFactory.php
│   │   ├── BorrowingFactory.php
│   │   ├── ReservationFactory.php
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_10_14_000010_create_books_table.php
│   │   ├── 2026_10_14_000020_create_borrowings_table.php
│   │   ├── 2026_10_14_000030_setup_rls.php
│   │   ├── 2026_10_15_000040_create_reservations_table.php
│   │   └── 2026_10_16_000050_create_notifications_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
│
├── docker/
│   ├── create_db.sql
│   ├── entrypoint.sh
│   ├── nginx.conf
│   └── seed_once.php
│
│
├── public/
│   ├── .htaccess
│   ├── favicon.ico
│   ├── index.php
│   └── robots.txt
│
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views/
│       ├── welcome.blade.php
│       ├── auth/
│       │   ├── login.blade.php
│       │   └── register.blade.php
│       ├── components/
│       │   ├── alert.blade.php
│       │   ├── badge.blade.php
│       │   ├── confirm.blade.php
│       │   ├── errors.blade.php
│       │   ├── icon.blade.php
│       │   ├── input.blade.php
│       │   ├── layout.blade.php
│       │   ├── modal.blade.php
│       │   ├── nav-item.blade.php
│       │   ├── notification-bell.blade.php
│       │   └── section.blade.php
│       ├── employee/
│       │   ├── books/
│       │   │   └── index.blade.php
│       │   ├── borrowings/
│       │   │   └── history.blade.php
│       │   ├── members/
│       │   │   └── index.blade.php
│       │   └── reservations/
│       │       └── index.blade.php
│       ├── member/
│       │   ├── books/
│       │   │   ├── index.blade.php
│       │   │   └── show.blade.php
│       │   ├── borrowings/
│       │   │   └── index.blade.php
│       │   └── reservations/
│       │       └── index.blade.php
│       └── vendor/
│           └── pagination/
│               └── tailwind.blade.php
│
├── routes/
│   ├── console.php
│   └── web.php
│
├── storage/
│   ├── app/
│   │   ├── private/
│   │   └── public/
│   ├── framework/
│   │   ├── cache/
│   │   │   └── data/
│   │   ├── sessions/
│   │   ├── testing/
│   │   └── views/
│   └── logs/
│
└── tests/
    ├── TestCase.php
    ├── Feature/
    │   ├── AuthTest.php
    │   ├── BorrowReturnTest.php
    │   ├── EmployeeBooksTest.php
    │   ├── EmployeeMembersTest.php
    │   ├── ExampleTest.php
    │   ├── MarkOverdueTest.php
    │   ├── MemberPortalTest.php
    │   ├── NotificationsCommandTest.php
    │   ├── NotificationsEventTest.php
    │   ├── NotificationsRlsTest.php
    │   ├── NotificationsServiceTest.php
    │   ├── NotificationsUiTest.php
    │   ├── PortalAccessTest.php
    │   ├── ReservationTest.php
    │   ├── RlsBootTest.php
    │   ├── SessionDeactivationTest.php
    │   └── ViewsSmokeTest.php
    └── Unit/
        └── ExampleTest.php
```

## Contributors
- [@erumiii](https://github.com/erumiii)
- [@saintbobi](https://github.com/saintbobi)
- [@tepgoo](https://github.com/tepgoo)
