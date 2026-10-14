# Library Management System — Design Spec

Basis: `PRD-Library-Management-System.md` v1.0 + `design.md` (visual guide). PRD open questions answered inline in PRD. This spec records the technical decisions made at design time.

## 1. Tech Decisions

| Decision        | Choice                                    | Reason                                                             |
| --------------- | ----------------------------------------- | ------------------------------------------------------------------ |
| Backend         | Laravel 12 (PHP, runs via XAMPP php/serve) | PRD stack                                                          |
| Database        | PostgreSQL 16 in Docker, port **5433**    | User has no local Postgres; dedicated container for this project   |
| Frontend        | Blade + Tailwind (Vite), no Livewire      | Server-rendered; design.md is pure Blade/Tailwind tokens; smallest surface |
| Auth            | 1 guard, `users.role` column (`employee`/`member`) | PRD models single `users` table with role enum            |
| Authz           | `EnsureRole` middleware (`role:employee`, `role:member`), cross-portal = 403 | PRD 8.2                            |
| Overdue         | Command `borrowings:mark-overdue` (schedule:everyMinute) + UI fallback calc from `due_at` | PRD 8.5          |
| RLS             | Non-superuser DB role `library_app`; policies on `borrowings` + `users`; per-request `set_config` | PRD 8.6, NFR-04 |
| Timezone/format | `Asia/Jakarta`, display `d/m/Y H:i`       | PRD business rule 8                                                |

## 2. Data Model

`users`: id, name, username (unique, case-insensitive index), password (hash), role enum('employee','member'), timestamps. Soft-deletable? **No** — members deactivated instead (PRD open Q5: employee can edit/deactivate member accounts).

`books`: id, title, author, publisher, page (int > 0), isbn (unique), status enum('isAvailable','isBorrowed') default 'isAvailable', softDeletes (PRD open Q4), timestamps.

`borrowings`: id, book_id FK, member_id FK→users, processed_by FK→users, borrowed_at, borrow_period_days (int, ≤7), due_at (stored, = borrowed_at + period), returned_at nullable, status enum('borrowed','returned','overdue') default 'borrowed', timestamps. Index (member_id, status), (book_id, status).

`memcached` not needed. No other tables.

## 3. Routes

```
GET  /                     → redirect by role
GET  /login                → shared login (single form)
POST /login
POST /logout
GET  /register             → member registration only
POST /register

Employee (auth + role:employee, prefix /employee):
  GET    /employee/books            (list + search q, status filter)
  GET    /employee/books/create
  POST   /employee/books
  GET    /employee/books/{book}/edit
  PUT    /employee/books/{book}
  DELETE /employee/books/{book}     (blocked while borrowed)
  GET    /employee/members          (list, deactivate/activate, edit)
  GET    /employee/members/{user}/edit
  PUT    /employee/members/{user}
  GET    /employee/borrowings/create   (Borrow book form)
  POST   /employee/borrowings
  GET    /employee/returns/create      (Return book form)
  POST   /employee/returns
  GET    /employee/borrowings/history  (Borrow history)

Member (auth + role:member, prefix /member):
  GET  /member/books               (collection + search)
  GET  /member/books/{book}        (detail)
  GET  /member/borrowings          (my borrowed books + reminder)
```

## 4. Business Logic

- Borrow: validate book `isAvailable`, member has < 3 active borrows, period 1–7 days. Wrap in transaction: create borrowing, set book `isBorrowed`. Assign `processed_by = auth employee`.
- Return: find active borrowing (borrowed/overdue) for book+member (or by borrowing id), set `returned_at`, status `returned`, book back to `isAvailable`. Transaction.
- Overdue marker command: `status = borrowed` AND `due_at < now()` → `overdue`.
- Reminder text: due − now → "X hari lagi" / "Terlambat X hari" / "Hari ini".
- Book delete: soft delete; blocked with error if active borrowing exists (AC-12).
- Member list for employee = `users` where role=member (FR-08 automatic).
- Member edit: name, password reset optional, active flag (`is_active` bool on users).
- Search: books by title/author/ISBN; filter by status.

## 5. RLS

- Migration (SQL, not schema builder) executed after schema:
  - Create non-superuser role `library_app` (app connects as this role; superuser owns schema).
  - `ALTER TABLE borrowings/users ENABLE + FORCE ROW LEVEL SECURITY`.
  - Policies: `borrowings` SELECT/UPDATE USING (current_setting('app.current_user_id')::bigint = member_id OR current_setting('app.current_role') = 'employee'); INSERT/DELETE employee-only. `users`: member sees own row, employee sees all; member UPDATE own row limited via app logic (policy allows own-row update).
- Middleware `SetDbSession`: after auth, runs `SELECT set_config('app.current_user_id', ?, true)` + role within transaction (local config = true → lasts for transaction; use `false` with per-request pool not applicable — Laravel persistent connection per request, use session-scoped config with `is_local=false` set at connect... **Decision: use `set_config(..., false)` at connection bootstrap per request; config persists on that pooled connection for request lifetime.** Reset not needed (FPM per-request connections).
- Tests connect as `library_app` role → AC-10 verifiable in feature tests.
- Migration runner connects as superuser (migrations own tables); app runtime uses `library_app`.

## 6. UI (per design.md)

- Layout: sidebar 256px `#f6f6f6`, header 64px border `#ececec`, content white, padding-left 68px, sections with divider, 3-col grid forms, Inter font, monochrome, status pill badges neutral.
- Sidebar: app name + logo block; groups: Dashboard-ish page list per portal (Employee: Books, Members, Borrow Book, Return Book, Borrow History; Member: Book Collection, My Borrowed Books); logout at bottom.
- Blade components: `x-nav-item`, `x-input`, `x-badge`, `x-section`.
- Tailwind config extended with design.md tokens (colors, fontSize, spacing, radius, shadows).

## 6.5 Employee Account Model

Single shared employee account owned by the library (seeded, not self-registered). No employee CRUD in UI; employee account changes go through seeder/DBA directly. Reflected in: no employee management page, register flow hardcodes `role=member`.

## 7. Validation & Security (PRD §10)

- Form Requests for all writes; CSRF on; password min 8; username unique case-insensitive (validate with `strtolower` uniqueness rule); login rate limit (5/min); session regenerate on login, destroy on logout; Eloquent only.

## 8. Testing

- Feature tests mapping AC-01..AC-12 (RLS AC-10 via `library_app` connection in test).
- Self-check commands runnable; no external test frameworks beyond PHPUnit (bundled).
- Seed: **exactly 1 shared employee account** (`library`/`password` — the library's own account, used by whichever employee is on shift; single account, not per-person). Employee accounts are never creatable via UI (no employee registration path exists). A few sample books.
- `role=employee` can never be set via register endpoint (register creates members only, hardcoded role).
- Docker: `docker compose up -d` → migrate (superuser) → seed.

## 9. Out of Scope

Guest access, social login, fines, reservations, notifications, multi-copy books (PRD out-of-scope list).
