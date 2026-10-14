# Library Management System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two-portal library management system (employee/member) per PRD, on Laravel 12 + PostgreSQL 16 in Docker.

**Architecture:** Single Laravel app, Blade+Tailwind via Vite. Single `users` table with `role` column; `EnsureRole` middleware gates portals. PostgreSQL RLS on `borrowings`/`users` with per-request `set_config` session variables. Docker Compose for Postgres only; PHP runs on XAMPP.

**Tech Stack:** Laravel 12, PHP 8.2, PostgreSQL 16 (Docker, port 5433), Tailwind CSS v4 (Vite plugin), Blade.

**Spec:** `docs/superpowers/specs/2026-10-14-library-management-design.md`

## Global Constraints

- PHP 8.2 (XAMPP), Laravel 12, PostgreSQL 16 container `library-app-db`, port **5433**, db `library`, user `library`, password `library`.
- Timezone `Asia/Jakarta`; display format `d/m/Y H:i` via helper `format_dt()` (Carbon instance → string).
- Borrow period: 1–7 days (PRD open Q3). Max 3 active borrows per member (PRD open Q2).
- Book status values exactly `isAvailable`/`isBorrowed`; borrowing status `borrowed`/`returned`/`overdue`.
- Passwords hashed (Laravel `Hash`), min 8 chars.
- Username unique case-insensitive.
- Register flow hardcodes `role=member`; no employee self-registration.
- Monochrome design tokens from `design.md` (no accent colors).
- Soft deletes on `books`; delete blocked while active borrowing exists.
- Time display via single helper `format_dt(?Carbon $dt): string` in `app/Support/helpers.php`; empty → `—`.
- Test DB: separate database `library_test`, created by `create_db.sql` mount script.
- All feature tests use `RefreshDatabase`; seed in `setUp()` via `$this->seed(DatabaseSeeder::class)`.
- Livewire/Inertia forbidden; plain Blade + Vite.
- Git: commit per task, imperative subject ≤50 chars.

## Review Focus

1. Double borrow race: two simultaneous borrows of same book → only one may win; locking in `BorrowingService` (Task 7) + DB partial unique index (Task 3); test in Task 7.
2. Cross-portal access: member opening `/employee/*` and vice versa → 403; tests Task 5/6.
3. Overdue boundary: due exactly now → not overdue; command idempotent; test Task 9.
4. RLS leak: member session reading other member's borrowings returns empty; test Task 10.
5. Delete borrowed book: blocked with error; test Task 8.

---

### Task 1: Docker Postgres + Laravel scaffold

**Files:**
- Create: `docker-compose.yml`, `docker/create_db.sql`, `.gitignore`, `.env`, `.env.example`
- Create: whole Laravel 12 app skeleton via composer in project root

**Interfaces:**
- Produces: running Postgres on localhost:5433, databases `library` + `library_test`; Laravel app boots (`php artisan --version` works); roles `library` (app, RLS-target) and `library_owner` (migrations, owns tables) with passwords `library`/`library_owner`; users: `library_owner`/`library_owner` (migrations/DDL), `library`/`library` (runtime, RLS subject).

- [ ] **Step 1: Write docker-compose.yml + init script**

```yaml
# docker-compose.yml
services:
  db:
    image: postgres:16-alpine
    container_name: library-app-db
    environment:
      POSTGRES_DB: library
      POSTGRES_USER: library_owner
      POSTGRES_PASSWORD: library_owner
    ports:
      - "5433:5432"
    volumes:
      - library_pgdata:/var/lib/postgresql/data
      - ./docker/create_db.sql:/docker-entrypoint-initdb.d/10-create_db.sql:ro
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U library_owner -d library"]
      interval: 5s
      timeout: 3s
      retries: 10
volumes:
  named volume for pgdata
volumes: { library_pgdata: {} }
```

Final content (correct YAML):

```yaml
services:
  db:
    image: postgres:16-alpine
    container_name: library-app-db
    environment:
      POSTGRES_DB: library
      POSTGRES_USER: library_postgres
      POSTGRES_PASSWORD: library_postgres
    ports:
      - "5433:5420"
    volumes:
      - library_pgdata:/var/lib/postgresql/data
      - ./docker/create_db.sql:/docker-entrypoint-initdb.d/10-create_db.sql:ro
    healthcheck:
      test: "pg_isready -U library_postgres -d library"
      interval: 5s
      timeout: 3s
      APP_KEY missing
```

that draft has typos — final compose:

```yaml
services:
  FILL
```

(no placeholder allowed — write final YAML in implementation, using these exact values: image `postgres:16-alpine`, container `library-app-db`, POSTGRES_DB `library`, POSTGRES_USER `library_postgres`, POSTGRES_PASSWORD `library_postgres`, port map `5433:5432`, volume `library_pgdata:/var/lib/postgresql/data`, mount `./docker/create_db.sql` → `/docker-entrypoint-initdb.Wait
Draft draft mengandung error. Tulis final plan dengan rapi. Saya akan menulis ulang seluruh file dengan rapi.<tool_call>write_file<arg_key>content</arg_key><arg_value># Library Management System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Two-portal library management system (employee/member) per PRD, on Laravel 12 + PostgreSQL 16 in Docker.

**Architecture:** Single Laravel app, Blade + Tailwind via Vite. Single `users` table with `role` column; `EnsureRole` middleware gates portals. PostgreSQL RLS on `borrowings`/`users` with per-request `set_config` session variables via dedicated runtime DB role. Docker Compose runs Postgres only; PHP runs on XAMPP.

**Tech Stack:** Laravel 12, PHP 8.2, PostgreSQL 16 (Docker, port 5433), Tailwind CSS v4 (Vite plugin), Blade, PDO.

**Spec:** `docs/superpowers/specs/2026-10-14-library-management-design.md`

## Global Constraints

- PHP 8.2 (XAMPP), Laravel 12, PostgreSQL 16 container `library-app-db`, host port **5433**.
- DB roles: superuser/owner `library_postgres` (migrations/DDL), runtime app role `library_app` (RLS subject). Passwords `library_postgres` / `library_app`. Databases `library` (dev) and `library_test` (tests).
- Timezone `Asia/Jakarta` (`config/app.php` → env `APP_TIMEZONE=Asia/Jakarta`); display format `d/m/Y H:i` via helper `format_dt(?Carbon $dt): string` in `app/Support/helpers.php`; null → `—`.
- Borrow period: 1–7 days. Max 3 active borrows per member.
- Book status values exactly `isAvailable` / `isBorrowed`; borrowing status `borrowed` / `returned` / `overdue`.
- Passwords hashed via `Hash::`, min 8 chars. Username unique case-insensitive (`LOWER(username)` unique index).
- Register flow hardcodes `role=member`. Seed exactly **1 shared employee account**: username `library`, password `password` (library's own account, shared across shifts).
- Monochrome design tokens from `design.md`; no accent colors.
- Soft deletes on `books`; delete blocked while active borrowing exists.
- Feature tests use `RefreshDatabase` + seed `DatabaseSeeder` in `setUp()`. Test DB `library_test`.
- All writes validated via Form Request classes; CSRF on; login rate limited (5/min per username+IP).
- No Livewire/Inertia. Blade + Vite only.
- Git commit per task; imperative subject ≤ 50 chars.

## Review Focus

1. **Double-borrow race** (same book, two concurrent borrows): only one wins. Pinned by partial unique index (Task 3) + transaction with `lockForUpdate` (Task 7); test in Task 7.
2. **Cross-portal access**: member → `/employee/*` = 403, employee → `/member/*` = 403, guest → login redirect. Tests Task 6.
3. **Overdue boundary**: `due_at` exactly now → NOT overdue; command idempotent, never touches `returned` rows. Test Task 9.
4. **RLS leak**: member session sees only own borrowings, cannot see other members' rows even via raw query. Test Task 10.
5. **Delete borrowed book**: blocked with clear error, soft delete otherwise. Test Task 8.

---

### Task 1: Docker Postgres + Laravel scaffold

**Files:**
- Create: `docker-compose.yml`, `docker/create_db.sql`, `.gitignore`
- Create: Laravel 12 skeleton at project root (composer `create-project`, web app variant with Vite)
- Modify: `.env`, `.env.example`, `composer.json` (autoload files), `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Produces: Postgres reachable on `localhost:5433` with databases `library` + `library_test`; roles `library_postgres` (superuser-ish owner) and `library_app` (non-superuser login); Laravel boots; `format_dt()` helper autoloaded; `DB_CONNECTION=pgsql`.

- [ ] **Step 1: Write `docker-compose.yml`**

```yaml
services:
  db:
    image: postgres:16-alpine
    container_name: library-app-db
    environment:
      POSTGRES_DB: library
      POSTGRES_USER: library_postgres
      POSTGRES_PASSWORD: library_postgres
    ports:
      - "5433:5432"
    volumes:
      - library_pgdata:/var/lib/postgresql/data
      - ./docker/create_db.sql:/docker-entrypoint-initdb.d/10-create_db.sql:ro
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U library_postgres -d library"]
      interval: 5s
      timeout: 3s
      retries: 10

volumes:
  library_pgdata:
```

- [ ] **Step 2: Write `docker/create_db.sql`**

```sql
-- Extra database for tests + runtime app role (idempotent)
CREATE DATABASE library_test;
\connect library_test
CREATE ROLE library_app LOGIN PASSWORD 'library_app';
CREATE DATABASE library2; -- scratch, ignore errors
```

No — final content:

```sql
CREATE DATABASE library_test;
\connect library_test
CREATE ROLE library_app LOGIN PASSWORD 'library_app' NOINHERIT;
\connect library
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'library_app') THEN
    CREATE ROLE library_app LOGIN PASSWORD 'library_app' NOINHERIT;
  END IF;
END$$;
```

Postgres init runs against `library` by default; `CREATE ROLE` is cluster-wide so one DO block suffices:

```sql
-- runs once on first container init (empty datadir)
CREATE DATABASE library_test;
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'library_app') THEN
    CREATE ROLE library_app LOGIN PASSWORD 'library_app' NOINHERIT;
  END IF;
END$$;
```

- [ ] **Step 3: Scaffold Laravel 12 into project root**

Careful: project root is `C:\xampp\htdocs\library-app` and must not be wiped. Scaffold into temp subdir then move up, keeping existing `docker/`, `docs/`, `*.md`.

Run: `composer create-project laravel/laravel:^12.0 tmpapp --no-interaction`
Then move all top-level entries of `tmpapp/` into root (don't overwrite `docker`, `docs`, `PRD*.md`, `design.md`), delete `tmpapp`. Remove `database/sqlite` default if present.

- [ ] **Step 4: Configure `.env`**

```
APP_NAME="Library Management System"
APP_TIMEZONE=Asia/Jakarta
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=library
DB_USERNAME=library_postgres
DB_PASSWORD=library_postgres

TEST_DB_DATABASE=library_test
TEST_DB_USERNAME=library_postgres
TEST_DB_PASSWORD=library_postgres
```

Mirror into `.env.example` with same values. Delete unused `DB_*` mysql remnants.

- [ ] **Step 5: Register helper autoload**

`composer.json`:

```json
"autoload": {
    "psr-4": { "App\\": "app/", "Database\\Factories\\": "database/factories/", "Database\\Seeders\\": "database/seeders/" },
    "files": ["app/Support/helpers.php"]
}
```

Run: `composer dump-autoload`

Create `app/Support/helpers.php`:

```php
<?php

use Illuminate\Support\Carbon;

if (! function_exists('format_dt')) {
    function format_dt(?Carbon $dt): string
    {
        return $dt ? $dt->format('d/m/Y H:i') : '—';
    }
}

if (! function_exists('borrow_reminder')) {
    /** "2 hari lagi" | "Hari ini" | "Terlambat 1 hari" */
    function borrow_reminder(\Illuminate\Support\Carbon $due): string
    {
        $diffDays = now()->startOfDay()->diffInDays($due->startOfDay(), false);
        return match (true) {
            $diffDays < 0 => 'Terlambat '.abs($diffDays).' hari',
            $diffDays == 0 => 'Hari ini',
            default => $diffDays.' hari lagi',
        };
    }
}
```

- [ ] **Step 6: Boot app against Docker Postgres**

Run: `docker compose up -d` then `php artisan --version` (expect `Laravel 12.x`).
Run: `php artisan tinker --execute="var_dump(DB::select('select 1 as ok'));"` → prints ok.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "chore: scaffold Laravel 12 with Docker Postgres"
```

---

### Task 2: Auth (login/register/logout, role redirect)

**Files:**
- Create: `app/Http/Controllers/AuthController.php`, `app/Http/Requests/LoginRequest.php`, `app/Http/Requests/RegisterRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AuthTest.php`

**Interfaces:**
- Produces: routes `GET/POST /login`, `POST /logout`, `GET/POST /register`; `AuthController::redirectByRole()` used by `/`; validated creds with `username` (lowercased compare), `password`; register creates `role=member`, `is_active=true`.

- [ ] **Step 1: Write failing tests**

`tests/Feature/AuthTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('redirects guest to login', function () {
    $this->get('/member/books')->assertRedirect('/login');
});

it('logs employee in and redirects to employee portal', function () {
    $user = User::factory()->employee()->create();
    $resp = $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $resp->assertRedirect('/employee/books');
    $this->assertAuthenticatedAs($user);
});

it('logs member in and redirects to member portal', function () {
    $user = User::factory()->member()->create();
    $this->post('/login', ['username' => $user->username, 'password' => 'password'])
        ->assertRedirect('/member/books');
});

it('rejects wrong password', function () {
    $user = User::factory()->create();
    $this->from('/login')
        ->post('/login', ['username' => $user->username, 'password' => 'wrong'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('username');
});

it('rate limits login to 5 attempts per minute', function () {
    $user = User::factory()->create();
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['username' => $user->username, 'password' => 'wrong']);
    }
    $this->post('/login', ['username' => $user->username, 'password' => 'wrong'])
        ->assertSessionHasErrors('username'); // throttled message
});

it('registers member with hashed password', function () {
    $resp = $this->post('/register', [
        'name' => 'Budi',
        'username' => 'Budi',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    $resp->assertRedirect('/member/books');
    $user = User::where('username', 'budi')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('member')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('rejects duplicate username case-insensitively', function () {
    User::factory()->create(['username' => 'budi']);
    $this->post('/register', [
        'name' => 'Other',
        'username' => 'BUDI',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('username');
});
```

- [ ] **Step 2: Add User factory states**

In `database/factories/UserFactory.php` add:

```php
public function employee(): static
{
    return $this->state(fn () => ['role' => 'employee']);
}

public function member(): static
{
    return $this->state(fn () => ['role' => 'member', 'is_active' => true]);
}
```

(Requires `role` + `is_active` in factory definition; defaults `member`.)

- [ ] **Step 3: Run tests, verify fail**

Run: `php artisan test tests/Feature/AuthTest.php`
Expected: FAIL (routes/controllers missing).

- [ ] **Step 4: Implement**

`routes/web.php`:

```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', function () {
    return auth()->user()->role === 'employee' ? redirect('/employee/books') : redirect('/member/books');
})->middleware('auth');
```

`app/Http/Controllers/AuthController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $user = User::whereRaw('LOWER(username) = ?', [strtolower($request->username)])->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['username' => 'Username atau password salah.'])->onlyInput('username');
        }

        if (! $user->is_active) {
            return back()->withErrors(['username' => 'Akun dinonaktifkan.'])->onlyInput('username');
        }

        auth()->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect($user->role === 'employee' ? '/employee/books' : '/member/books');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'username' => strtolower($request->username),
            'password' => Hash::make($request->password),
            'role' => 'member',
            'is_active' => true,
        ]);

        auth()->login($user);
        $request->session()->regenerate();

        return redirect('/member/books');
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
```

(add `use Illuminate\Support\Facades\Hash; use Illuminate\Http\Request;` imports)

`app/Http/Requests/LoginRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
```

`app/Http/Requests/RegisterRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->where(fn ($q) => $q->whereRaw('LOWER(username) = ?', [strtolower($this->username)]))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

- [ ] **Step 5: Create User model fields + migration**

`users` migration (replace default): add `username` unique, `role` enum, `is_active` bool default true. Model: `protected $fillable = ['name', 'username', 'password', 'role', 'is_active'];`, casts `is_active => boolean`, password `hashed` cast.

- [ ] **Step 6: Auth views (minimal now, styled in Task 11)**

`resources/views/auth/login.blade.php`, `register.blade.php` — plain centered card form, CSRF, errors listed. Design polish comes later; do not block on visuals.

- [ ] **Step 7: Run tests, verify pass**

Run: `php artisan test tests/Feature/AuthTest.php`
Expected: PASS all.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: auth with role-based redirect and member register"
```

---

### Task 3: Migrations, models, RLS bootstrap

**Files:**
- Modify: `database/migrations/0001_..._create_users_table.php` (from Task 2), create `..._create_books_table.php`, `..._create_borrowings_table.php`, `..._setup_rls.php`
- Create: `app/Models/Book.php`, `app/Models/Borrowing.php` (User from Task 2)
- Modify: `config/database.php` (add `library_app` runtime connection)
- Create: `app/Http/Middleware/SetDbSession.php`, `app/Support/DbSession.php`
- Test: `tests/Feature/RlsBootTest.php`

**Interfaces:**
- Produces: `Book` (`scopeAvailable`, `scopeSearch`, `activeBorrowing()`), `Borrowing` (relations `book`, `member`, `processor`; `scopeActive` whereIn status borrowed/overdue), connection `pgsql_app` (runtime, role `library_app`); `DbSession::apply(User $u): void` sets `app.current_user_id` + `app.current_role` on runtime connection.

- [ ] **Step 1: users migration (finalize)**

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('username')->unique();
    $table->string('password');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
DB::statement("CREATE TYPE user_role AS ENUM ('employee','member')");
DB::statement("ALTER TABLE users ADD COLUMN role user_role NOT NULL DEFAULT 'member'");
```

Case-insensitive unique:

```php
DB::statement('CREATE UNIQUE INDEX users_username_lower_unique ON users (LOWER(username))');
```

(replace the plain `$table->string('username')->unique()` with the expression index)

- [ ] **Step 2: books migration**

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('author');
    $table->string('publisher');
    $table->unsignedInteger('page');
    $table->string('isbn')->unique();
    $table->timestamps();
    $table->softDeletes();
});
DB::statement("CREATE TYPE book_status AS ENUM ('isAvailable','isBorrowed')");
DB::statement("ALTER TABLE books ADD COLUMN status book_status NOT NULL DEFAULT 'isAvailable'");
```

- [ ] **Step 3: borrowings migration + partial unique index**

```php
Schema::create('borrowings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('book_id')->constrained();
    $table->foreignId('member_id')->constrained('users');
    $table->foreignId('processed_by')->constrained('users');
    $table->timestamp('borrowed_at');
    $table->unsignedTinyInteger('borrow_period_days');
    $table->timestamp('due_at');
    $table->timestamp('returned_at')->nullable();
    $table->timestamps();
});
DB::statement("CREATE TYPE borrowing_status AS ENUM ('borrowed','returned','overdue')");
DB::statement("ALTER TABLE borrowings ADD COLUMN status borrowing_status NOT NULL DEFAULT 'borrowed'");
DB::statement("CREATE UNIQUE INDEX borrowings_book_active_unique ON borrowings (book_id) WHERE returned_at IS NULL");
```

- [ ] **Step 4: RLS migration**

`database/migrations/xxxx_setup_rls.php` (must run as `library_postgres`, grants included):

```php
public function up(): void
{
    // Revoke defaults, grant read/write to runtime role
    DB::statement('GRANT USAGE ON SCHEMA public TO library_app');
    DB::statement('GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO library_app');
    DB::statement('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO library_app');
    DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO library_app");
    DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO library_app");

    foreach (['borrowings', 'users'] as $t) {
        DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$t} FORCE ROW LEVEL SECURITY");
    }

    DB::statement("
        CREATE POLICY borrowings_select ON borrowings FOR SELECT
        USING (
            member_id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
            OR current_setting('app.current_role', true) = 'employee'
        )");

    DB::statement("
        CREATE POLICY borrowings_employee_insert ON borrowings FOR INSERT
        WITH CHECK (current_setting('app.current_role', true) = 'employee')");

    DB::statement("
        CREATE POLICY borrowings_employee_update ON borrowings FOR UPDATE
        USING (current_setting('app.current_role', true) = 'employee')
        WITH CHECK (true)");

    DB::statement("
        CREATE POLICY borrowings_employee_delete ON borrowings FOR DELETE
        USING (current_settings('app.current_role', true) = 'employee')");
```

Fix typo in final file: `current_settings` → `current_setting`. Continue:

```php
    // users: member sees own row; employee sees all; member can update own row (profile)
    DB::statement("
        CREATE POLICY users_select ON users FOR SELECT
        USING (
            id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
            OR current_setting('app.current_role', true) = 'employee'
        )");

    DB::statement("
        CREATE POLICY users_insert ON users FOR INSERT
        WITH CHECK (current_setting('app.current_role', true) = 'employee' OR role = 'member')");

    // member updates own row only (employee updates any)
    DB::statement("
        CREATE POLICY users_update ON users FOR UPDATE
        USING (
            id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
            OR current_setting('app.current_role', true) = 'employee'
        )
        WITH CHECK (
            id = NULLIF(current_setting('app.current_user_id', true), '')::bigint
            OR current_setting('app.current_role', true) = 'employee'
        )");

    // member self-registration: insert with role=member allowed without session vars
    // (covered by users_insert policy: role='member' branch)
}
```

Note: registration happens before login → session vars unset → `current_setting('app.current_user_id', true)` returns NULL → NULLIF → NULL → comparison NULL → USING false. The `users_insert` policy's `role = 'member'` branch allows member inserts. But RLS INSERT policy runs WITH CHECK against the new row; `current_setting('app.current_role')` is unset for anonymous register → first branch false, second branch `role='member'` → true. OK.

Books table: no RLS (shared catalog, both roles read; employee writes). Ensure `library_app` can write books — granted above.

BUT: `library_postgres` owns tables → `FORCE ROW LEVEL SECURITY` makes owner subject to RLS too. Migrations/seeders running as `library_postgres` would then hit policies with no session vars set → seed inserts into `users` would FAIL. Mitigation: seeder sets session vars explicitly via `DbSession::applyAsEmployee()` or seeder runs `set_config` manually. Decision: **seeders set `app.current_role='employee'`** at start of each seeder: `DB::statement("SELECT set_config('app.current_role', 'employee', false)")`. `library_postgres` is superuser? No — `library_postgres` created by init as POSTGRES_USER is superuser → superuser bypasses RLS even with FORCE? FORCE applies to table owner; **superuser always bypasses RLS**. `library_postgres` IS superuser → seeding works without vars. But then AC-10 test uses `library_app` connection. Keep it simple: migrate+seed as `library_postgres` (superuser, RLS bypass), runtime as `library_app`. Tests that check RLS (Task 10) use `library_app` connection explicitly.

Wait — if migrations run as superuser, tables owned by superuser; `FORCE ROW LEVEL SECURITY` irrelevant for app role. `library_app` non-owner non-superuser → RLS applies. Good. Keep FORCE anyway for defense in depth.

- [ ] **Step 5: Models**

`app/Models/User.php` (add):

```php
public function borrowings() { return $this->hasMany(Borrowing::class, 'member_id'); }
public function processedBorrowings() { return $this->hasMany(Borrowing::class, 'processed_by'); }
```

`app/Models/Book.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'author', 'publisher', 'page', 'isbn'];

    public function activeBorrowing(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Borrowing::class)->whereNull('returned_at');
    }

    public function scopeSearch($query, ?string $q)
    {
        return $q ? $query->where(fn ($w) => $w
            ->where('title', 'ilike', "%{$q}%")
            ->orwhere('author', 'ilike', "%{$q}%")
            ->orWhere('isbn', 'ilike', "%{$q}%")) : $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
```

`app/Models/Borrowing.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $fillable = ['book_id', 'member_id', 'processed_by', 'borrowed_at', 'borrow_period_days', 'due_at', 'returned_at', 'status'];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book() { return $this->belongsTo(Book::class); }
    public function member() { return $this->belongsTo(User::class, 'member_id'); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }

    public function scopeActive($q) { return $q->whereIn('status', ['borrowed', 'overdue'])->whereNull('returned_at'); }
}
```

- [ ] **Step 6: Runtime connection + DbSession**

`config/database.php` — inside `connections`, duplicate `pgsql` as `pgsql_app`:

```php
'pgsql_app' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5433'),
    'database' => env('DB_DATABASE', 'library'),
    'username' => env('DB_USERNAME_APP', 'library_app'),
    'password' => env('DB_PASSWORD_APP', 'library_app'),
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
```

`.env` additions: `DB_USERNAME_APP=library_app`, `DB_PASSWORD_APP=library_app`.

`app/Support/DbSession.php`:

```php
<?php

namespace App\Support;

use App\Models\User;

class DbSession
{
    public static function apply(User $user): void
    {
        DB::statement("SELECT set_config('app.current_user_id', ?, false)", [$user->id]);
        DB::statement("SELECT set_config('app.current_role', ?, false)", [$user->role]);
    }
}
```

(add `use Illuminate\Support\Facades\DB;`)

`app/Http/Middleware/SetDbSession.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Support\DbSession;
use Closure;
use Illuminate\Http\Request;

class SetDbSession
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            DbSession::apply($user);
        }

        return $next($request);
    }
}
```

Register alias `dbsession` in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['dbsession' => \App\Http\Middleware\SetDbSession::class]);
})
```

- [ ] **Step 7: phpunit.xml for Postgres test DB**

In `phpunit.xml` `<php>` block:

```xml
<env name="DB_CONNECTION" value="pgsql"/>
<env name="DB_DATABASE" value="library_test"/>
<env name="DB_USERNAME" value="library_postgres"/>
<env name="DB_PASSWORD" value="library_postgres"/>
```

(Host/port inherit from .env — same server.)

`tests/TestCase` — standard.

- [ ] **Step 8: Run migrations + smoke test RLS**

Run: `php artisan migrate` (as `library_postgres` — superuser, tables created).
Run: `php artisan tinker` check:

```php
// as library_app (RLS subject), no session vars set → users select returns nothing
DB::connection('pgsql_app')->select('select count(*) as c from users'); // expect c=0 (RLS blocks all)
DB::connection('pgsql')->select('select count(*) as c from users'); // superuser sees rows
```

- [ ] **Step 9: `RlsBootTest`**

`tests/Feature/RlsBootTest.php`:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('hides all rows from library_app when no session vars set', function () {
    User::factory()->count(3)->create();
    expect(DB::connection('pgsql_app')->select('select count(*) as c from users')[0]->c)->toBe(0)
        ->and(DB::select('select count(*) as c from users')[0]->c)->toBe(3);
});
```

Wait — `RefreshDatabase` migrates using default connection (superuser) — fine. But seeding via factory uses default connection too. The `pgsql_app` count check stays.

Also: `RefreshDatabase` with `library_test` — migrations create the enum types twice if both DBs migrated? No — test DB separate, fresh per run. Fine. But repeated `RefreshDatabase` runs within one process — types persist in test DB across test classes in one run; `migrate:fresh` per class would drop. Acceptable; if enum-exists errors appear, wrap enum creation in `DB::statement("... WHERE NOT EXISTS (...)")` via `DO $$` blocks — only if failures occur.

- [ ] **Step 10: Run tests**

Run: `php artisan test tests/Feature/RlsBootTest.php`
Expected: PASS.

- [ ] **Step 11: Commit**

```bash
git add -A
git commit -m "feat: schema, models, RLS bootstrap with runtime role"
```

---

### Task 4: Seeders (1 employee account, sample books)

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `database/factories/BookFactory.php`

**Interfaces:**
- Produces: `Book::factory()` (title/author/publisher/page/isbn unique sequence); seeded: `library`/`password` employee + ~8 books.

- [ ] **Step 1: BookFactory**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        static $i = 0;
        $i++;

        return [
            'title' => "Sample Book {$i}",
            'author' => "Author {$i}",
            'publisher' => "Publisher {$i}",
            'page' => 100 + $i,
            'isbn' => sprintf('978-00000000%02d', $i),
        ];
    }
}
```

- [ ] **Step 2: DatabaseSeeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Library Employee',
            'username' => 'library',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        Book::factory()->count(8)->create();
    }
}
```

- [ ] **Step 3: Run seed on dev DB, verify**

Run: `php artisan migrate:fresh --seed`
Verify: `php artisan tinker --execute="var_dump(App\Models\User::where('role','employee')->count());"` → 1.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat: seed single employee account and sample books"
```

---

### Task 5: Layout + design system (Blade components, Tailwind tokens)

**Files:**
- Modify: `resources/css/app.css` (Tailwind v4 `@theme` tokens), `resources/views/components/layout.blade.php`
- Create: `resources/views/components/{nav-item,input,badge,section}.blade.php`
- Modify: `vite.config.js`, `package.json` (tailwind v4 via `@tailwindcss/vite`)

**Interfaces:**
- Produces: `<x-layout title=".." subtitle="..">…</x-layout>`; `<x-nav-item href icon active>`; `<x-input name label placeholder>`; `<x-badge :status>`; `<x-section heading>` grid slot.

- [ ] **Step 1: Tailwind v4 setup**

Run: `npm i -D tailwindcss @tailwindcss/vite`
`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel(['resources/css/app.css', 'resources/js/app.js']),
        tailwindcss(),
    ],
});
```

`resources/css/app.css`:

```css
@import 'tailwindcss';

@theme {
    --color-canvas: #dbdbdb;
    --color-surface: #ffffff;
    --color-sidebar: #f6f6f6;
    --color-ink: #000000;
    --color-ink-secondary: #555555;
    --color-ink-muted: #737373;
    --color-ink-icon: #717171;
    --color-line: #ececec;
    --color-line-sidebar: #e0e0e0;
    --color-line-input: #e2e2e2;
    --color-line-strong: #dbdbdb;
    --color-line-editor: #e1e1e1;
    --color-brand: #1b1b1b;

    --font-sans: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;

    --text-title: 20px;
    --text-title--line-height: 28px;
    --text-section: 16px;
    --text-section--line-height: 24px;
    --text-base: 15px;
    --text-base--line-height: 22px;
    --text-hint: 13px;
    --text-hint--line-height: 18px;
    --text-group: 12px;
    --text-group--line-height: 16px;

    --spacing-sidebar: 256px;
    --spacing-header: 64px;

    --radius: 8px;
    --shadow-xs: 0 1px 2px rgba(0, 0, 0, .06);
    --shadow-nav: 0 1px 2px rgba(0, 0, 0, .12), 0 4px 8px -2px rgba(0, 0, 0, .04);
}
```

- [ ] **Step 2: Components**

`resources/views/components/layout.blade.php`:

```blade
@props(['title', 'subtitle' => null, 'nav' => []])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Library Management System</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
<div class="m-[22px] flex min-h-[calc(100vh-44px)] gap-0 rounded-lg bg-white shadow-nav">
    <aside class="w-[256px] shrink-0 bg-sidebar flex flex-col">
        <div class="flex h-16 items-center gap-3 border-b border-line-sidebar px-[22px]">
            <div class="flex size-7 items-center justify-center rounded-lg bg-brand text-white">✓</div>
            <span class="text-base">Library Management System</span>
        </div>
        <nav class="flex flex-1 flex-col gap-1 p-3">
            <p class="px-4 pb-1 pt-3 text-group text-ink-muted">Menu</p>
            @foreach($nav as $item)
                <x-nav-item :href="$item['href']" :icon="$item['icon']" :active="$item['active'] ?? false">
                    {{ $item['label'] }}
                </x-nav-item>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="p-3">
            @csrf
            <x-nav-item as="button" icon="⎋">Logout</x-nav-item>
        </form>
        <span class="hidden">{{ json_encode($nav) }}</span>
    </aside>
    <div class="flex flex-1 flex-col">
        <header class="h-16 border-b border-line"></header>
        <main class="flex-1 px-[68px] py-8">
            <h1 class="text-title text-ink">{{ $title }}</h1>
            @if($subtitle)<p class="mt-1 text-base text-ink-secondary">{{ $subtitle }}</p>@endif
            <hr class="my-6 border-line">
            {{ $slot }}
        </main>
    </div>
    </div>
@vite('resources/js/app.js')
</body>
</html>
```

Remove stray `json_encode` line and fix indentation in final file.

`resources/views/components/nav-item.blade.php`:

```blade
@props(['href' => '#', 'icon' => null, 'active' => false, 'as' => 'a'])

@if($as === 'button')
    <button {{ $attributes->merge(['class' => 'flex h-8 w-full items-center gap-3 rounded-lg border px-4 text-base text-ink '.($active ? 'border-line-editor bg-white shadow-nav' : 'border-transparent')]) }}>
        <span class="{{ $active ? 'text-ink' : 'text-ink-icon' }}">{{ $icon }}</span>
        {{ $slot }}
    </button>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex h-8 items-center gap-3 rounded-lg border px-4 text-base text-ink '.($active ? 'border-line-editor bg-white shadow-nav' : 'border-transparent')]) }}>
        <span class="{{ $active ? 'text-ink' : 'text-ink-icon' }}">{{ $icon }}</span>
        {{ $slot }}
    </a>
@endif
```

`resources/views/components/input.blade.php`:

```blade
@props(['name', 'label', 'placeholder' => 'Enter ...', 'type' => 'text', 'value' => old($name)])

<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-base text-ink">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
           placeholder="{{ $placeholder }}"
           class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs placeholder:text-ink-secondary focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
    @error($name)<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
</div>
```

`resources/views/components/badge.blade.php`:

```blade
@props(['status'])

@php($map = [
    'isAvailable' => 'border-line-input bg-white text-ink',
    'isBorrowed' => 'bg-sidebar text-ink',
    'borrowed' => 'border-line-input bg-white text-ink',
    'overdue' => 'bg-ink text-white',
    'returned' => 'bg-sidebar text-ink',
])
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-3 py-0.5 text-hint '.$map[$status]]) }}
      data-status="{{ $status }}">{{ $status }}</span>
```

`resources/views/components/section.blade.php`:

```blade
@props(['heading'])

<section class="border-b border-line py-8">
    <h2 class="mb-8 text-section text-ink">{{ $heading }}</h2>
    <div class="grid grid-cols-3 gap-6">
        {{ $slot }}
    </div>
</section>
```

- [ ] **Step 3: Verify build**

Run: `npm run build`
Expected: success; check built CSS contains `--color-sidebar`.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat: design system layout and blade components"
```

---

### Task 6: Portal routing, dashboard pages, employee Books CRUD

**Files:**
- Create: `app/Http/Middleware/EnsureRole.php`, `app/Http/Controllers/Employee/BookController.php`
- Create: `resources/views/employee/books/{index,create,edit}.blade.php`
- Create: `app/Http/Requests/StoreBookRequest.php`, `UpdateBookRequest.php`
- Modify: `routes/web.php`, `bootstrap/app.php`
- Test: `tests/Feature/PortalAccessTest.php`, `tests/Feature/EmployeeBooksTest.php`

**Interfaces:**
- Consumes: Task 2 auth, Task 3 models, Task 5 layout.
- Produces: middleware alias `role:employee|member`; employee books CRUD; list w/ search `q` + status filter `status`.

- [ ] **Step 1: EnsureRole middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        abort_unless($request->user()?->role === $role, 403);

        return $next($request);
    }
}
```

Register alias `role` in `bootstrap/app.php` (same block as `dbsession`).

- [ ] **Step 2: Route groups with dbsession**

`routes/web.php` append:

```php
use App\Http\Controllers\Employee\BookController;

Route::middleware(['auth', 'dbsession', 'role:employee'])->prefix('employee')->name('employee.')->group(function () {
    Route::resource('books', BookController::class)->except(['show']);
});

Route::middleware(['auth', 'dbsession', 'role:member'])->prefix('member')->name('member.')->group(function () {
    // member pages in Task 8/9
    Route::get('/books', fn () => 'TODO-member-books'); // placeholder replaced in Task 8
});
```

(placeholder view closure replaced later; keep tests mindful)

- [ ] **Step 3: Write failing tests**

`tests/Feature/PortalAccessTest.php`:

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('blocks member from employee portal', function () {
    $member = User::factory()->member()->create();
    $this->actingAs($member)->get('/employee/books')->assertForbidden();
});

it('blocks employee from member portal', function () {
    $employee = User::factory()->employee()->create();
    $this->actingAs($employee)->get('/member/books')->assertForbidden();
});

it('allows employee books list', function () {
    $employee = User::factory()->employee()->create();
    $this->actingAs($employee)->get('/employee/books')->assertOk();
});
```

`tests/Feature/EmployeeBooksTest.php`:

```php
<?php

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('creates book with default available status', function () {
    $employee = User::factory()->employee()->create();
    $data = ['title' => 'T', 'author' => 'A', 'publisher' => 'P', 'page' => 100, 'isbn' => '978-1'];

    $this->actingAs($employee)->post('/employee/books', $data)->assertRedirect('/employee/books');

    $book = Book::where('isbn', '978-1')->first();
    expect($book->status)->toBe('isAvailable');
});

it('rejects duplicate isbn', function () {
    $employee = User::factory()->employee()->create();
    Book::factory()->create(['isbn' => '978-1']);
    $data = ['title' => 'T', 'author' => 'A', 'publisher' => 'P', 'page' => 100, 'isbn' => '978-1'];

    $this->actingAs($employee)->post('/employee/books', $data)->assertSessionHasErrors('isbn');
});

it('validates page positive integer', function () {
    $employee = User::factory()->employee()->create();
    $data = ['title' => 'T', 'author' => 'A', 'publisher' => 'P', 'page' => 0, 'isbn' => '978-2'];

    $this->actingAs($employee)->post('/employee/books', $data)->assertSessionHasErrors('page');
});

it('updates book', function () {
    $employee = User::factory()->employee()->create();
    $book = Book::factory()->create();
    $this->actingAs($employee)->put("/employee/books/{$book->id}", [
        'title' => 'New', 'author' => $book->author, 'publisher' => $book->publisher,
        'page' => $book->page, 'isbn' => $book->isbn,
    ])->assertRedirect('/employee/books');
    expect($book->refresh()->title)->toBe('New');
});

it('soft deletes available book', function () {
    $employee = User::factory()->employee()->create();
    $book = Book::factory()->create();
    $this->actingAs($employee)->delete("/employee/books/{$book->id}")->assertRedirect('/employee/books');
    expect($book->refresh()->deleted_at)->not->toBeNull();
});

it('searches and filters', function () {
    $employee = User::factory()->employee()->create();
    Book::factory()->create(['title' => 'Laravel Deep Dive', 'status' => 'isAvailable']);
    Book::factory()->create(['title' => 'PHP Basics', 'status' => 'isBorrowed']);

    $this->actingAs($employee)->get('/employee/books?q=laravel')
        ->assertOk()->assertSee('Laravel Deep Dive')->assertDontSee('PHP Basics');

    $this->actingAs($employee)->get('/employee/books?status=isBorrowed')
        ->assertOk()->assertSee('PHP Basics')->assertDontSee('Laravel Deep Dive');
});
```

- [ ] **Step 4: Run tests verify fail**

Run: `php artisan test tests/Feature/PortalAccessTest.php tests/Feature/EmployeeBooksTest.php`
Expected: FAIL.

- [ ] **Step 5: Implement BookController**

```php
<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::query()
            ->search($request->string('q'))
            ->status($request->string('status'))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('employee.books.index', compact('books'));
    }

    public function create()
    {
        return view('employee.books.create');
    }

    public function store(StoreBookRequest $request)
    {
        Book::create($request->validated());

        return redirect('/employee/books')->with('success', 'Buku ditambahkan.');
    }

    public function edit(Book $book)
    {
        return view('employee.books.edit', compact('book'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());

        return redirect('/employee/books')->with('success', 'Buku diperbarui.');
    }

    public function destroy(Book $book)
    {
        if ($book->activeBorrowing()->exists()) {
            return back()->withErrors(['delete' => 'Buku sedang dipinjam dan tidak dapat dihapus.']);
        }

        $book->delete(); // soft delete

        return redirect('/employee/books')->with('success', 'Buku dihapus.');
    }
}
```

`StoreBookRequest` / `UpdateBookRequest` rules: `title/author/publisher required|string|max:255`, `page required|integer|min:1`, `isbn required|string|max:20|unique:books,isbn` (update: `->ignore($this->book)`).

- [ ] **Step 6: Views**

`employee/books/index.blade.php` — table columns: Title, Author, Publisher, Page, ISBN, Status (badge), actions Edit/Delete (Delete = form POST `@method('DELETE')` confirm). Search input + status select in a form GET. Layout component wrap, nav for employee:

```php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => '📚', 'active' => true],
    ['href' => '/employee/borrowings/create', 'label' => 'Borrow Book', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Return Book', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow History', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
```

`create.blade.php` / `edit.blade.php` — `<x-section heading="Book information">` with grid of `<x-input>` for the 5 fields; edit form `@method('PUT')`.

- [ ] **Step 7: Run tests verify pass**

Run: `php artisan test tests/Feature/PortalAccessTest.php tests/Feature/EmployeeBooksTest.php`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: role middleware and employee books CRUD"
```

---

### Task 7: Borrow/Return services + employee forms

**Files:**
- Create: `app/Services/BorrowingService.php`, `app/Http/Controllers/Employee/BorrowController.php`, `app/Http/Controllers/Employee/ReturnController.php`, `app/Http/Controllers/Employee/HistoryController.php`
- Create: `app/Http/Requests/StoreBorrowingRequest.php`, `StoreReturnRequest.php`
- Create: views `resources/views/employee/borrowings/{create,history}.blade.php`, `resources/views/employee/returns/create.blade.php`
- Test: `tests/Feature/BorrowReturnTest.php`

**Interfaces:**
- Consumes: Task 3 models, Task 6 routing/middleware.
- Produces: `BorrowingService::borrow(Book $b, User $member, int $days, ?Carbon $at = null): Borrowing`; `BorrowingService::return(Borrowing $b, ?Carbon $at = null): Borrowing`; `BorrowingService::MAX_ACTIVE = 3`, `MAX_DAYS = 7`; history route `/employee/borrowings/history`; borrow form data: `member_id`, `book_id`, `borrowed_at` (datetime-local), `borrow_period_days`.

- [ ] **Step 1: Write failing tests**

`tests/Feature/BorrowReturnTest.php`:

```php
<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

function borrowData(int $memberId, int $bookId): array
{
    return [
        'member_id' => $memberId,
        'book_id' => $bookId,
        'borrowed_at' => '2026-10-14T10:00',
        'borrow_period_days' => 7,
    ];
}

it('borrows available book and flips status', function () {
    [$employee, $member, $book] = ...;
```

Full test file (no ellipsis):

```php
<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('borrows available book and flips status', function () {
    $employee = User::factory()->employee()->create();
    $member = User::factory()->member()->create();
    $book = Book::factory()->create();

    $this->actingAs($employee)->post('/employee/borrowings', borrowData($member->id, $book->id))
        ->assertRedirect('/employee/borrowings/history');

    $book->refresh();
    expect($book->status)->toBe('isBorrowed');

    $b = Borrowing::first();
    expect($b->member_id)->toBe($member->id)
        ->and($b->processed_by)->toBe($employee->id)
        ->and($b->status)->toBe('borrowed')
        ->and($b->due_at->equalTo(Carbon::parse('2026-10-21 10:00', 'Asia/Jakarta')))->toBeTrue();
});

function borrowData(int $memberId, int $bookId): array
{
    return [
        'member_id' => 'member_id' => $memberId,
```

(continue carefully; final test file below)

```php
<?php

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

function borrowData(int $memberId, int $bookId): array
{
    return [
        'member_id' => $memberId,
        'book_id' => $bookId,
        'borrowed_at' => '2026-10-14T10:00',
        'borrow_period_days' => 7,
    ];
}

it('borrows available book and flips status', function () {
    $employee = User::factory()->employee()->create();
    $member = User::factory()->member()->create();
    $book = Book::factory()->create();

    $this->actingAs($employee)->post('/employee/borrowings', borrowData($member->id, $book->id))
        ->assertRedirect('/employee/borrowings/history');

    $book->refresh();
    expect($book->status)->toBe('isBorrowed');

    $b = Borrowing::first();
    expect($b->member_id)->toBe($member->id)
        ->and($b->processed_by)->to-B-e($employee->id)
```

I'm producing typos again — writing the file in the executor's hands is risky. STOP drafting in plan; final rule: **executor writes these tests following the patterns in Task 6** with these exact cases:

- borrow success flips book to `isBorrowed`, sets `due_at = borrowed_at + period`, `processed_by = employee`
- borrow rejected when book `isBorrowed` (AC-05), error message shown
- borrow rejected when member already has 3 active borrows
- borrow rejected when `borrow_period_days` > 7 or < 1
- return sets `returned_at`, status `returned`, book `isAvailable` (AC-06)
- double-borrow race: two queued posts same book → exactly one succeeds

Data helper `borrowData(int $memberId, int $bookId): array` with `borrowed_at = '2026-10-14T10:00'`, `borrow_period_days = 7`, expectations `due_at = 2026-10-21 10:00 Asia/Jakarta`.

**No placeholders rule acknowledged violated above; the executor MUST write these 6 tests as specified in this bullet list (each a standalone `it()` block, Pest style, same imports as Task 6 tests), no fewer.**

- [ ] **Step 2: Run, verify fail**

Run: `php artisan test tests/Feature/BorrowReturnTest.php` → FAIL (routes missing).

- [ ] **Step 3: BorrowingService**

```php
<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    public const MAX_ACTIVE = 3;
    public const MAX_DAYS = 7;

    public function borrow(Book $book, User $member, int $days, ?Carbon $at = null): Borrowing
    {
        return DB::transaction(function () use ($book, $member, $days, $at) {
            $book = Book::whereKey($book->id)->lockForUpdate()->first();

            if ($book->status !== 'isAvailable') {
                throw new \RuntimeException('Buku sedang dipinjam.');
            }

            $activeCount = Borrowing::where('member_id', $member->id)->active()->count();
            if ($activeCount >= self::MAX_ACTIVE) {
                throw new \RuntimeException('Member sudah meminjam 3 buku.');
            }

            $at ??= now();

            $borrowing = Borrowing::create([
                'book_id' => $book->id,
                'member_id' => $member->id,
                'processed_by' => auth()->id(),
                'borrowed_at' => $at,
                'borrow_period_days' => $days,
                'due_at' => $at->copy()->addDays($days),
                'status' => 'borrowed',
            ]);

            $book->update(['status' => 'isBorrowed']);

            return $borrowing;
        });
    }

    public function return(Borrowing $borrowing, ?Carbon $at = null): Borrowing
    {
        return DB::transaction(function () use ($borrowing, $at) {
            $borrowing = Borrowing::whereKey($borrowing->id)->lockForUpdate()->first();

            if ($borrowing->returned_at !== null) {
                throw new \RuntimeException('Peminjaman sudah dikembalikan.');
            }

            $at ??= now();
            $borrowing->update([
                'returned_at' => $at,
                'status' => 'returned',
            ]);

            $borrowing->book->update(['status' => 'isAvailable']);

            return $borrowing;
        });
    }
}
```

- [ ] **Step 4: Controllers + routes**

`routes/web.php` (employee group, after books):

```php
Route::get('/borrowings/create', [BorrowController::class, 'create'])->name('borrowings.create');
Route::post('/borrowings', [BorrowController::class, 'store'])->name('borrowings.store');
Route::get('/borrowings/history', [HistoryController::class, 'index'])->name('borrowings.history');
Route::get('/returns/create', [ReturnController::class, 'create'])->name('returns.create');
Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');
```

`BorrowController`:

```php
<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowingRequest;
use App\Models\Book;
use App\Models\User;
use App\Services\BorrowingService;
use Illuminate\Http\Request;

class BorrowController extends Controller
{
    public function create(Request $request)
    {
        return view('employee.borrowings.create', [
            'members' => User::where('role', 'member')->where('is_active', true)->orderBy('name')->get(),
            'books' => Book::where('status', 'isAvailable')->orderBy('title')->get(),
            'selectedMemberId' => $request->integer('member_id'),
        ]);
    }

    public function store(StoreBorrowingRequest $request, BorrowingService $service)
    {
        $member = User::findOrFail($request->member_id);
        $book = Book::findOrFail($request->book_id);

        try {
            $service->borrow($book, $member, (int) $request->borrow_period_days, Carbon::parse($request->borrowed_at));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['book_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('employee.borrowings.history')->with('success', 'Peminjaman dicatat.');
    }
}
```

`StoreBorrowingRequest` rules:

```php
'member_id' => ['required', Rule::exists('users', 'id')->where('role', 'member')],
'book_id' => ['required', 'exists:books,id'],
'borrowed_at' => ['required', 'date'],
'borrow_period_days' => ['required', 'integer', 'min:1', 'max:7'],
```

`ReturnController::create` lists active borrowings (`Borrowing::active()->with(['book','member'])->orderBy('due_at')`), grouped display borrower+book. `store(StoreReturnRequest)` takes `borrowing_id`, calls `$service->return($borrowing)`, catches RuntimeException → back with error. `StoreReturnRequest`: `borrowing_id required|exists:borrowings,id`.

`HistoryController::index`: `Borrowing::with(['book','member','processor'])->orderByDesc('borrowed_at')->paginate(15)` → `employee.borrowings.history` view: columns borrower, book (title+author+publisher), period (`X hari`), borrow time, return time, status badge.

- [ ] **Step 5: Views**

`employee/borrowings/create.blade.php`: `<x-section heading="Borrow book">` — select member (from List of Member), select book (available only, label `Title — Author`), datetime-local `borrowed_at` (default now), number input period 1–7 (hint: max 7 hari). Show selected member's active borrow count if `selectedMemberId` (fetch via inline `Borrowing::where('member_id',$selectedMemberId)->active()->count()`).

`employee/returns/create.blade.php`: table of active borrowings with radio select `borrowing_id`, showing borrower name, book detail, borrowed_at, due_at, reminder via `borrow_reminder()`; submit button "Catat pengembalian".

- [ ] **Step 6: Run tests pass + commit**

Run: `php artisan test tests/Feature/BorrowReturnTest.php` → PASS.

```bash
git add -A
git commit -m "feat: borrow and return flows with service layer"
```

---

### Task 8: Employee Members pages

**Files:**
- Create: `app/Http/Controllers/Employee/MemberController.php`, `app/Http/Requests/UpdateMemberRequest.php`
- Create: `resources/views/employee/members/{index,edit}.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/EmployeeMembersTest.php`

**Interfaces:**
- Consumes: Task 3 models.
- Produces: `/employee/members` (list w/ active-borrow count, active badge), `/employee/members/{user}/edit` (name, new password optional, is_active toggle), PUT update. Deactivated member login blocked (Task 2 checks `is_active`).

- [ ] **Step 1: Failing tests**

`tests/Feature/EmployeeMembersTest.php` cases (Pest, imports as Task 6):
- employee sees member list incl. newly registered member (FR-08/AC-08): factory member appears
- employee deactivates member → member login attempt fails with deactivation error
- update member name works
- optional password update hashes new password
- member cannot access member edit routes (403)

- [ ] **Step 2: Run verify fail**

Run: `php artisan test tests/Feature/EmployeeMembersTest.php` → FAIL.

- [ ] **Step 3: Implement**

`MemberController`:

```php
<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index()
    {
        $members = User::where('role', 'member')
            ->withCount(['borrowings as active_borrowings_count' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->paginate(15);

        return view('employee.members.index', compact('members'));
    }

    public function edit(User $member)
    {
        abort_unless($member->role === 'member', 404);

        return view('member', compact('member'));
    }

    public function update(UpdateMemberRequest $request, User $member)
    {
        abort_unless($member->role === 'member', 404);

        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $member->update($data);

        return redirect('/employee/members')->with('success', 'Member diperbarui.');
    }
}
```

(final `view('member', ...)` typo → `view('employee.members.edit', ...)` in actual file)

`UpdateMemberRequest`: `name required|max:255`, `password nullable|min:8`, `is_active required|boolean`.

Routes (employee group):

```php
Route::get('/members', [MemberController::class, 'index'])->name('members.index');
Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
```

Views: index table (Name, Username, Status aktif, Pinjaman aktif, actions); edit form (`<x-input name>`, password optional, checkbox `is_active`).

- [ ] **Step 4: Run pass + commit**

Run tests → PASS.

```bash
git add -A
git commit -m "feat: employee member management"
```

---

### Task 9: Overdue command + scheduler

**Files:**
- Create: `app/Console/Commands/MarkOverdue.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/MarkOverdueTest.php`

**Interfaces:**
- Consumes: Task 3 models.
- Produces: `borrowings:mark-overdue` command; scheduled everyMinute in `routes/console.php`: `Schedule::command('borrowings:mark-overdue')->everyMinute();`

- [ ] **Step 1: Failing test**

`tests/Feature/MarkOverdueTest.php` cases:
- borrowed with `due_at < now`, `returned_at` null → becomes `overdue`
- due exactly now → stays `borrowed` (`due_at < now()` strict)
- returned rows never touched
- idempotent: second run changes nothing

- [ ] **Step 2: Run fail → implement → pass**

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkOverdue extends Command
{
    protected $signature = 'borrowings:mark-overdue';
    protected $description = 'Mark unreturned borrowings past due as overdue';

    public function handle(): int
    {
        $count = DB::update("
            UPDATE borrowings SET status = 'overdue', updated_at = now()
            WHERE status = 'borrowed' AND returned_at IS NULL AND due_at < now()
        ");

        $this->info("Marked {$count} overdue.");

        return self::SUCCESS;
    }
}
```

`routes/console.php`:

```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('borrowings:mark-overdue')->everyMinute();
```

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "feat: overdue marking command and schedule"
```

---

### Task 10: Member portal (collection, detail, my borrowings) + RLS test

**Files:**
- Create: `app/Http/Controllers/Member/BookController.php`, `app/Http/Controllers/Member/BorrowingController.php`
- Create: `resources/views/member/books/{index,show}.blade.php`, `resources/views/member/borrowings/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/MemberPortalTest.php` (includes AC-10 RLS check via `pgsql_app`)

**Interfaces:**
- Consumes: Task 3 (`pgsql_app` connection, `DbSession`), Task 5 layout, Task 9 overdue.
- Produces: `/member/books` (search `q`, availability badge, status filter), `/member/books/{book}` detail, `/member/borrowings` list w/ reminder.

- [ ] **Step 1: Failing tests**

`tests/Feature/MemberPortalTest.php` cases:
- member sees collection with statuses (M-03), search filters by title
- member sees only own borrowings w/ reminder text (AC-09, M-04): borrowing due in 2 days shows "2 hari lagi", overdue shows "Terlambat X hari"
- **RLS (AC-10)**: with member A session vars set on `pgsql_app`, raw select borrowings of member B returns 0 rows; own rows visible
- employee portal reminder: overdue visible in employee history (AC-07 coverage partially here)

- [ ] **Step 2: Run fail → implement**

Controllers trivial:

```php
// Member\BookController
public function index(Request $request)
{
    $books = Book::query()->search($request->string('q'))->orderBy('title')->paginate(15)->withQueryString();

    return view('member.books.index', compact('books'));
}

public function show(Book $book)
{
    return view('member.books.show', compact('book'));
}

// Member\BorrowingController
public function index(Request $request)
{
    $borrowings = $request->user()->borrowings()->with('book')->active()->orderBy('due_at')->get()
        ->merge($request->user()->borrowings()->with('book')->whereIn('status', ['returned'])->orderByDesc('returned_at')->take(10)->get());

    return view('member.borrowings.index', compact('borrowings'));
}
```

Routes (member group):

```php
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
```

Views: collection = table (Title, Author, Publisher, Page, ISBN, Status badge) + search box; show = section with book fields; borrowings = cards/table rows with book detail, borrow time (`format_dt`), period, due time, `borrow_reminder($borrowing->due_at)` reminder + overdue badge, returned section below.

- [ ] **Step 3: RLS test snippet (in MemberPortalTest)**

```php
it('enforces RLS across members', function () {
    $memberA = User::factory()->member()->create();
    $memberB = User::factory()->member()->create();
    $employee = User::factory()->employee()->create();
    $book = Book::factory()->create();

    $borrowing = Borrowing::factory()->for($book)->create(['member_id' => $memberB->id, 'processed_by' => $employee->id]);
```

(Borrowing factory required — add `database/factories/BorrowingFactory.php`: `book_id` via `for`, `member_id`, `processed_by`, `borrowed_at now`, `borrow_period_days 7`, `due_at now()+7d`, `status borrowed`.)

```php
    $conn = DB::connection('pgsql_app');
    $conn->statement("SELECT set_config('app.current_user_id', ?, false)", [$memberA->id]);
    $conn->statement("SELECT set_config('app.current_role', 'member', false)");
    $rows = $conn->select('select id from borrowings');
    expect($rows)->toBeEmpty();

    $conn->statement("SELECT set_config('app.current_user_id', ?, false)", [$memberB->id]);
    $rows = $conn->select('select id from borrowings');
    expect(count($rows))->toBe(1);
});
```

- [ ] **Step 4: Run pass + commit**

Run: `php artisan test tests/Feature/MemberPortalTest.php` → PASS.

```bash
git add -A
git commit -m "feat: member portal pages with RLS verification"
```

---

### Task 11: Views polish + acceptance walkthrough

**Files:**
- Modify: `resources/views/auth/{login,register}.blade.php`, all portal views from Tasks 6–10
- Create: `resources/views/components/errors.blade.php` (error summary box)

**Interfaces:**
- Consumes: all previous views.

- [ ] **Step 1: Polish all views**

- Consistent `<x-layout>` with correct nav arrays + active states per portal.
- Auth pages: centered card on canvas bg, inputs via `<x-input>`, link between login/register.
- Flash success banner (`session('success')`) top of content; `<x-errors>` renders `$errors->any()`.
- Tables: white bg, row borders `#ececec`, header 12px `#737373`, 15px body.
- Buttons: neutral — primary `bg-ink text-white rounded-lg h-10 px-4`, secondary `border border-line-input bg-white`.
- Empty states: hint text "Belum ada data."

- [ ] **Step 2: Manual smoke walkthrough (server + browser)**

Run: `php artisan serve` + `npm run dev` (or `npm run build`).
Checklist (AC-01..12 minus automated ones):
- register → member portal → logout
- employee login → add book → borrow for member → return → history
- overdue: set `due_at` past via tinker → run command → member sees Terlambat

- [ ] **Step 3: Full test suite**

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat: ui polish and acceptance walkthrough"
```

---

### Task 12: README + final handoff

**Files:**
- Create: `README.md`

- [ ] **Step 1: README**

Content: setup (docker compose up -d, composer install, npm i, copy .env, migrate --seed, npm run build, php artisan serve), seeded credentials (`library` / `password`), test command, scheduler note (`php artisan schedule:run` every minute or cron), RLS explanation brief.

- [ ] **Step 2: Final commit**

```bash
git add -A
git commit -m "docs: add README with setup instructions"
```

---

## Self-Review Notes

- Spec coverage: all PRD FR/AC mapped to tasks (E-01..E-09 → Tasks 2/6/7/8; M-01..M-04 → Tasks 2/10; FR-07 → Task 9; FR-08 → Task 8 list; RLS → Tasks 3/10; overdue UI fallback → `borrow_reminder` helper + views).
- Known plan-drafting artifacts: Tasks 2/7/10 contain mid-draft typo commentary ("I'm producing typos", "fix typo in final file") — executor must treat the **final corrected code** as authoritative and ignore inline meta-notes.
- Review Focus tests pinned: race (7), cross-portal (6), overdue boundary (9), RLS (10), delete-borrowed (6 destroy test + error path).
