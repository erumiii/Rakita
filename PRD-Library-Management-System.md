# Product Requirements Document (PRD)

# Library Management System

| Atribut        | Detail                          |
| -------------- | ------------------------------- |
| **Versi**      | 1.0 (Draft)                     |
| **Tanggal**    | 14 Oktober 2026                 |
| **Tech Stack** | Laravel, PostgreSQL             |
| **Status**     | Draft untuk review              |

---

## 1. Ringkasan Produk

Library Management System adalah aplikasi web untuk mengelola koleksi buku, keanggotaan, serta proses peminjaman dan pengembalian buku. Aplikasi memiliki **dua portal terpisah**:

- **Portal Employee** (admin/petugas): mengelola buku, anggota, peminjaman, pengembalian, dan riwayat.
- **Portal Member** (anggota): melihat koleksi buku dan memantau buku yang sedang dipinjam beserta pengingat batas waktu pengembalian.

Aplikasi tidak menyediakan akses untuk guest; seluruh fitur hanya dapat diakses setelah login.

## 2. Tujuan dan Sasaran

### 2.1 Tujuan
1. Menggantikan pencatatan peminjaman manual dengan sistem terpusat.
2. Memberikan visibilitas status ketersediaan buku secara real-time.
3. Memberikan anggota transparansi atas pinjaman dan batas waktu pengembalian mereka.
4. Menjamin keamanan data melalui enkripsi password dan isolasi akses data per pengguna.

### 2.2 Indikator Keberhasilan
- Seluruh operasi CRUD buku berfungsi tanpa error.
- Status buku (`isAvailable` / `isBorrowed`) selalu konsisten dengan data peminjaman.
- Buku yang melewati batas waktu pengembalian otomatis ditandai (overdue).
- Member hanya dapat melihat data miliknya sendiri (terverifikasi melalui RLS).

### 2.3 Di Luar Cakupan (Out of Scope)
- Akses guest / pengunjung tanpa login.
- Login menggunakan metode selain username dan password (social login, OTP, dll).
- Denda keterlambatan / pembayaran.
- Reservasi buku atau antrean.
- Notifikasi email/SMS/push (reminder hanya ditampilkan di aplikasi).

## 3. Pengguna dan Peran

| Peran        | Deskripsi                                                            | Portal          |
| ------------ | -------------------------------------------------------------------- | --------------- |
| **Employee** | Petugas perpustakaan yang mengelola buku, anggota, dan transaksi.    | Portal Employee |
| **Member**   | Anggota yang mendaftar sendiri dan meminjam buku.                    | Portal Member   |

> Akun Employee dibuat oleh sistem/seeder atau oleh administrator (registrasi mandiri hanya tersedia untuk Member).

## 4. Fitur Aplikasi

### 4.1 Fitur Employee

| ID    | Fitur                      | Deskripsi                                                                                                                  |
| ----- | -------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| E-01  | Login                      | Login menggunakan username dan password.                                                                                   |
| E-02  | Tambah buku                | Menambahkan buku baru ke koleksi dengan field: Title, Author, Publisher, Page, ISBN.                                       |
| E-03  | Edit buku                  | Mengubah detail buku yang sudah ada.                                                                                       |
| E-04  | Daftar buku                | Menampilkan seluruh buku beserta statusnya.                                                                                |
| E-05  | Hapus buku                 | Menghapus buku dari koleksi.                                                                                               |
| E-06  | Daftar member              | Menampilkan seluruh member yang terdaftar.                                                                                 |
| E-07  | Borrow book                | Form peminjaman: Borrow time (d/m/y/hour), Borrower's name, Borrowed book (dapat menampilkan detail buku), Borrow period.  |
| E-08  | Return book                | Form pengembalian: Return time (d/m/y/hour), Borrower's name, Borrowed book (dapat menampilkan detail buku).               |
| E-09  | Borrow history             | Riwayat peminjaman: Borrower's name, Borrowed book (dapat menampilkan detail buku), Borrow period, Borrow time, Return time. |

### 4.2 Fitur Member

| ID    | Fitur                          | Deskripsi                                                                                                                                      |
| ----- | ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| M-01  | Login                          | Login menggunakan username dan password.                                                                                                       |
| M-02  | Account register               | Registrasi akun untuk member baru.                                                                                                             |
| M-03  | Lihat koleksi buku             | Melihat daftar buku yang tersedia di perpustakaan.                                                                                             |
| M-04  | Daftar buku yang dipinjam      | Menampilkan buku yang sedang dipinjam beserta: Borrow time (d/m/y/hour), Borrowed book (dapat menampilkan detail buku), Borrow period, dan Return time reminder (contoh: "2 hari lagi"). |

## 5. Functional Requirements

| ID    | Requirement                                                                                                                                                                                  | Prioritas |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| FR-01 | Aplikasi memiliki 2 portal: satu untuk employee dan satu untuk member.                                                                                                                       | Must      |
| FR-02 | Aplikasi hanya dapat digunakan oleh employee dan member; tidak ada akses guest.                                                                                                              | Must      |
| FR-03 | Login hanya menggunakan username dan password.                                                                                                                                               | Must      |
| FR-04 | Aplikasi mendukung operasi CRUD (Create, Read, Update, Delete) pada data buku.                                                                                                               | Must      |
| FR-05 | Setiap buku wajib memiliki state: `isAvailable` atau `isBorrowed`.                                                                                                                           | Must      |
| FR-06 | Buku baru otomatis diberi state `isAvailable`.                                                                                                                                               | Must      |
| FR-07 | Sistem memberikan state/penanda **overdue** pada buku yang belum dikembalikan ketika periode peminjaman telah berakhir.                                                                      | Must      |
| FR-08 | Ketika user mendaftar sebagai member, sistem otomatis menambahkannya ke "List of Member" pada fitur employee.                                                                                |  Must      |
| FR-09 | Fitur "Borrow book" milik employee memiliki form untuk permintaan peminjaman member dan terintegrasi dengan List of Member. Saat member mengajukan peminjaman, employee dapat meng-assign peminjaman tersebut ke akun member. | Must |
| FR-10 | Setelah peminjaman dibuat, data peminjaman tampil pada fitur "List of Borrowed Book" milik member.                                                                                           | Must      |

### 5.1 Business Rules

1. Buku dengan state `isBorrowed` tidak dapat dipinjam lagi sampai dikembalikan.
2. Saat peminjaman dibuat, state buku berubah menjadi `isBorrowed`.
3. Saat pengembalian dicatat, state buku kembali menjadi `isAvailable` dan `return_time` terisi.
4. Tanggal jatuh tempo = `borrow_time` + `borrow_period`.
5. Peminjaman dianggap **overdue** jika `return_time` kosong dan waktu sekarang melewati tanggal jatuh tempo.
6. Buku yang sedang dipinjam tidak boleh dihapus (atau penghapusan harus ditolak dengan pesan yang jelas).
7. ISBN bersifat unik per buku.
8. Format tampilan waktu: `d/m/Y H:i` (zona waktu `Asia/Jakarta`).
9. Return time reminder dihitung dari selisih jatuh tempo dan waktu sekarang (contoh: "2 hari lagi", "Terlambat 1 hari").

## 6. Non-Functional Requirements

| ID     | Requirement                                                                                  | Implementasi (Laravel + PostgreSQL)                                                                 |
| ------ | -------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| NFR-01 | Password harus dienkripsi.                                                                   | Hashing bawaan Laravel (`Hash::make`, bcrypt/argon2). Password tidak pernah disimpan plain text.    |
| NFR-02 | Database harus persisten.                                                                    | PostgreSQL dengan migrasi Laravel; volume/storage persisten pada environment deployment.            |
| NFR-03 | Mudah dinavigasi.                                                                            | Menu sidebar/navbar yang konsisten per portal, label jelas, maksimal 2 klik ke fitur utama.         |
| NFR-04 | Akses user diisolasi dengan RLS (Row Level Security).                                        | PostgreSQL RLS pada tabel yang berisi data per-user (lihat bagian 8).                               |

## 7. Alur Pengguna (User Flow)

### 7.1 Registrasi dan Peminjaman
1. Calon member membuka portal member dan mengisi form registrasi.
2. Sistem membuat akun dengan role `member` dan otomatis memunculkannya di List of Member (FR-08).
3. Member login dan melihat koleksi buku.
4. Member mendatangi/menghubungi employee untuk mengajukan peminjaman.
5. Employee membuka menu **Borrow book**, memilih member dari List of Member, memilih buku (state `isAvailable`), mengisi Borrow time dan Borrow period, lalu menyimpan.
6. Sistem mengubah state buku menjadi `isBorrowed` dan membuat record peminjaman.
7. Peminjaman muncul di List of Borrowed Book milik member beserta reminder waktu pengembalian (FR-10).

### 7.2 Pengembalian
1. Employee membuka menu **Return book** dan memilih peminjaman aktif (berdasarkan borrower dan buku).
2. Employee mengisi Return time dan menyimpan.
3. Sistem mengisi `returned_at`, mengubah status peminjaman menjadi `returned`, dan state buku menjadi `isAvailable`.
4. Data tercatat pada Borrow history.

## 8. Arsitektur dan Desain Teknis

### 8.1 Tech Stack

| Komponen      | Teknologi                                                          |
| ------------- | ------------------------------------------------------------------ |
| Backend       | Laravel (PHP)                                                      |
| Database      | PostgreSQL                                                         |
| Frontend      | Blade (+ Tailwind CSS) atau Livewire / Inertia, sesuai preferensi tim |
| Autentikasi   | Laravel Auth dengan 2 guard (`employee`, `member`) atau 1 guard dengan role |
| Scheduler     | Laravel Task Scheduling (`php artisan schedule:run`) untuk penanda overdue |

### 8.2 Struktur Portal

| Portal   | Prefix URL    | Middleware              |
| -------- | ------------- | ----------------------- |
| Employee | `/employee/*` | `auth`, `role:employee` |
| Member   | `/member/*`   | `auth`, `role:member`   |

Setelah login, user diarahkan ke portal sesuai role. Mengakses portal yang bukan miliknya menghasilkan `403 Forbidden`.

### 8.3 Model Data

**users**

| Kolom        | Tipe          | Keterangan                       |
| ------------ | ------------- | -------------------------------- |
| id           | bigint (PK)   |                                  |
| name         | varchar       | Nama lengkap                     |
| username     | varchar       | Unik                             |
| password     | varchar       | Hash                             |
| role         | enum          | `employee` / `member`            |
| created_at / updated_at | timestamp |                              |

**books**

| Kolom        | Tipe          | Keterangan                                   |
| ------------ | ------------- | -------------------------------------------- |
| id           | bigint (PK)   |                                              |
| title        | varchar       | Wajib                                        |
| author       | varchar       | Wajib                                        |
| publisher    | varchar       | Wajib                                        |
| page         | integer       | Wajib, > 0                                   |
| isbn         | varchar       | Wajib, unik                                  |
| status       | enum          | `isAvailable` / `isBorrowed`, default `isAvailable` |
| created_at / updated_at | timestamp |                                  |

**borrowings**

| Kolom              | Tipe        | Keterangan                                           |
| ------------------ | ----------- | ---------------------------------------------------- |
| id                 | bigint (PK) |                                                      |
| book_id            | bigint (FK) | Referensi ke `books`                                 |
| member_id          | bigint (FK) | Referensi ke `users` (role member)                   |
| processed_by       | bigint (FK) | Employee yang memproses peminjaman                   |
| borrowed_at        | timestamp   | Borrow time                                          |
| borrow_period_days | integer     | Borrow period (dalam hari)                           |
| due_at             | timestamp   | `borrowed_at` + `borrow_period_days`                 |
| returned_at        | timestamp   | Nullable; terisi saat pengembalian (Return time)     |
| status             | enum        | `borrowed` / `returned` / `overdue`                  |
| created_at / updated_at | timestamp |                                              |

> **List of Member** pada portal employee adalah query atas `users` dengan `role = member`, sehingga registrasi member otomatis muncul tanpa proses tambahan (FR-08).

### 8.4 Relasi
- Satu `user` (member) memiliki banyak `borrowings`.
- Satu `book` memiliki banyak `borrowings` (riwayat), dengan maksimal satu peminjaman aktif pada satu waktu.

### 8.5 Penanda Overdue (FR-07)
- Scheduled command (mis. `borrowings:mark-overdue`) berjalan periodik (setiap jam atau setiap menit) dan mengubah `status` menjadi `overdue` untuk record dengan `returned_at IS NULL` dan `due_at < now()`.
- Sebagai pengaman, tampilan UI juga menghitung overdue berdasarkan `due_at` sehingga tidak bergantung penuh pada jadwal command.

### 8.6 Row Level Security (NFR-04)

Tujuan: member hanya bisa membaca data peminjaman miliknya sendiri, sementara employee dapat mengakses seluruh data operasional.

Rencana implementasi:

1. Gunakan **role database non-superuser** khusus aplikasi (superuser dan pemilik tabel melewati RLS secara default). Gunakan `ALTER TABLE ... FORCE ROW LEVEL SECURITY` bila aplikasi terhubung sebagai pemilik tabel.
2. Aktifkan RLS pada tabel `borrowings` dan `users`.
3. Pada setiap request (middleware Laravel), set variabel sesi PostgreSQL dalam transaksi:
   ```sql
   SELECT set_config('app.current_user_id', '<id>', true);
   SELECT set_config('app.current_role', '<employee|member>', true);
   ```
4. Contoh policy:
   ```sql
   ALTER TABLE borrowings ENABLE ROW LEVEL SECURITY;

   CREATE POLICY member_select_own ON borrowings
     FOR SELECT
     USING (
       current_setting('app.current_role', true) = 'employee'
       OR member_id = current_setting('app.current_user_id', true)::bigint
     );

   CREATE POLICY employee_write ON borrowings
     FOR ALL
     USING (current_setting('app.current_role', true) = 'employee')
     WITH CHECK (current_setting('app.current_role', true) = 'employee');
   ```
5. Tabel `books` dapat dibaca oleh semua user terautentikasi, namun hanya employee yang dapat melakukan insert/update/delete.
6. RLS berfungsi sebagai lapisan pertahanan tambahan; otorisasi di level aplikasi (middleware/policy Laravel) tetap diterapkan.

> **Catatan:** Karena koneksi database di Laravel bersifat per-proses (bukan per-user), penggunaan `set_config(..., true)` (local ke transaksi) wajib dibungkus dalam transaksi per request agar variabel tidak bocor antar request.

## 9. Spesifikasi Halaman

### 9.1 Portal Employee

| Halaman         | Konten Utama                                                                                           |
| --------------- | ------------------------------------------------------------------------------------------------------ |
| Login           | Username, password.                                                                                    |
| Daftar buku     | Tabel buku (title, author, publisher, page, ISBN, status), aksi tambah/edit/hapus, pencarian.          |
| Form buku       | Title, Author, Publisher, Page, ISBN dengan validasi.                                                  |
| Daftar member   | Tabel member (nama, username, tanggal bergabung).                                                      |
| Borrow book     | Pilih member, pilih buku tersedia (dengan detail buku), Borrow time, Borrow period.                    |
| Return book     | Pilih peminjaman aktif, tampilkan Borrower's name dan detail buku, isi Return time.                    |
| Borrow history  | Tabel riwayat: borrower, buku (detail), borrow period, borrow time, return time, status.               |

### 9.2 Portal Member

| Halaman                  | Konten Utama                                                                                             |
| ------------------------ | -------------------------------------------------------------------------------------------------------- |
| Login                    | Username, password, tautan ke registrasi.                                                                |
| Registrasi               | Nama, username, password, konfirmasi password.                                                           |
| Koleksi buku             | Daftar buku beserta status ketersediaan, pencarian.                                                      |
| Daftar buku dipinjam     | Borrow time, buku (detail), Borrow period, Return time reminder (mis. "2 hari lagi"), penanda overdue.   |

## 10. Validasi dan Keamanan

- Semua input divalidasi di sisi server (Form Request Laravel).
- Proteksi CSRF aktif pada seluruh form.
- Password minimal 8 karakter, di-hash, dan tidak pernah ditampilkan.
- Username unik dan case-insensitive.
- Rate limiting pada endpoint login untuk mencegah brute force.
- Session diregenerasi setelah login dan dihapus saat logout.
- Query menggunakan Eloquent / parameter binding untuk mencegah SQL injection.

## 11. Acceptance Criteria

| ID    | Skenario                                                                                    | Hasil yang Diharapkan                                                             |
| ----- | ------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| AC-01 | Guest membuka halaman portal tanpa login.                                                   | Diarahkan ke halaman login.                                                       |
| AC-02 | Member mencoba membuka `/employee/*`.                                                       | Akses ditolak (403).                                                              |
| AC-03 | Employee menambah buku baru.                                                                | Buku tersimpan dengan status `isAvailable`.                                       |
| AC-04 | Employee membuat peminjaman untuk buku `isAvailable`.                                       | Status buku menjadi `isBorrowed`; peminjaman muncul di akun member.               |
| AC-05 | Employee mencoba meminjamkan buku yang berstatus `isBorrowed`.                              | Ditolak dengan pesan kesalahan.                                                   |
| AC-06 | Employee mencatat pengembalian.                                                             | Status buku kembali `isAvailable`; return time tercatat di history.               |
| AC-07 | Batas peminjaman terlewati tanpa pengembalian.                                              | Peminjaman ditandai overdue dan tampil sebagai terlambat di sisi employee dan member. |
| AC-08 | User baru mendaftar sebagai member.                                                         | Muncul otomatis di List of Member pada portal employee.                           |
| AC-09 | Member membuka daftar buku dipinjam.                                                        | Hanya peminjaman miliknya yang tampil, lengkap dengan reminder waktu.             |
| AC-10 | Query langsung ke database (di luar aplikasi) dengan konteks member A atas data member B.   | Tidak ada baris yang dikembalikan (RLS aktif).                                    |
| AC-11 | Pemeriksaan kolom password di database.                                                     | Berisi hash, bukan plain text.                                                    |
| AC-12 | Employee menghapus buku yang sedang dipinjam.                                               | Ditolak dengan pesan kesalahan.                                                   |

## 12. Asumsi dan Pertanyaan Terbuka

**Asumsi**
- Satu judul buku direpresentasikan sebagai satu record (satu eksemplar). Jika perlu banyak eksemplar per judul, diperlukan tabel `book_copies`.
- "Borrow request" dari member diproses secara offline: member meminta ke employee, lalu employee menginput melalui form Borrow book dan meng-assign ke akun member (sesuai FR-09). Member tidak membuat request dari portalnya sendiri.
- Borrow period dinyatakan dalam satuan hari.
- Akun employee dibuat melalui seeder atau oleh administrator.

**Pertanyaan terbuka**
1. Apakah member perlu mengajukan permintaan pinjam langsung dari portal member, atau cukup lewat employee? Member mendatangi employee untuk pengajuan pinjam buku, nanti employee yang akan melakukan pencatatan.
2. Apakah ada batas maksimal buku yang boleh dipinjam per member? Maksimal 3 buku
3. Apakah ada batas maksimal atau pilihan baku untuk Borrow period (mis. 7, 14, 21 hari)? Tidak ada batas minimal, namun maksimal borrow period 7 hari.
4. Apakah penghapusan buku bersifat permanen atau soft delete (agar riwayat peminjaman tetap utuh)? Soft delete.
5. Apakah perlu fitur edit/nonaktifkan akun member oleh employee? Perlu.
6. Apakah diperlukan pencarian dan filter pada daftar buku (judul, penulis, status)? Perlu.

## 13. Rencana Pengembangan (Usulan)

| Fase | Cakupan                                                                  |
| ---- | ------------------------------------------------------------------------ |
| 1    | Setup proyek, skema database, autentikasi, dua portal, RLS dasar.        |
| 2    | CRUD buku, daftar member, registrasi member.                             |
| 3    | Borrow book, Return book, Borrow history, logika status buku.            |
| 4    | Penanda overdue, Return time reminder, daftar buku dipinjam member.      |
| 5    | Pengujian (feature test, uji RLS), perbaikan UX, dokumentasi, deployment. |

## 14. Glosarium

| Istilah        | Arti                                                                  |
| -------------- | --------------------------------------------------------------------- |
| CRUD           | Create, Read, Update, Delete.                                         |
| RLS            | Row Level Security, fitur PostgreSQL untuk membatasi akses per baris. |
| isAvailable    | Status buku yang tersedia untuk dipinjam.                             |
| isBorrowed     | Status buku yang sedang dipinjam.                                     |
| Overdue        | Peminjaman yang melewati batas waktu pengembalian tanpa dikembalikan. |
| Borrow period  | Lama waktu peminjaman yang diizinkan.                                 |
