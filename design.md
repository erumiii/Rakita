# Design Guide

Panduan desain UI yang diekstrak dari dua screenshot referensi (sidebar "Pedicure practice" + halaman **Home** berisi form). Dokumen ini dipakai sebagai acuan implementasi di Laravel (Blade + Tailwind CSS / CSS biasa) agar tampilan **sama dengan referensi**.

---

## 0. Cara Nilai Diperoleh (Penting)

- Warna diambil langsung dari piksel screenshot (nilai hex terukur). Warna teks abu-abu bertanda **(est.)** karena anti-aliasing membuat nilai persisnya tidak bisa dibaca 100%.
- Font dicocokkan dengan mengukur lebar teks nyata dan membandingkannya dengan metrik **Inter**; hasilnya konsisten di semua string (selisih < 3%).
- Screenshot tidak memiliki skala absolut. Skala **1 CSS px = 1,2 px screenshot** dipilih karena menghasilkan angka bulat yang masuk akal (header 64px, sidebar 256px, input 40px, gap 24px). Semua ukuran di bawah sudah dikonversi ke **CSS px** dengan skala ini.
- Bila saat dibandingkan hasil akhir terasa sedikit lebih besar/kecil, ubah semua ukuran secara proporsional, jangan satu per satu.

---

## 1. Warna

### 1.1 Palet

| Token                    | Hex        | Dipakai untuk                                                        |
| ------------------------ | ---------- | -------------------------------------------------------------------- |
| `--color-canvas`         | `#dbdbdb`  | Latar luar di belakang aplikasi                                      |
| `--color-surface`        | `#ffffff`  | Area konten utama, input, nav item aktif                             |
| `--color-sidebar`        | `#f6f6f6`  | Latar sidebar                                                        |
| `--color-text`           | `#000000`  | Judul, label, teks nav, teks input, nama organisasi                  |
| `--color-text-secondary` | `#555555` (est.) | Subjudul halaman, placeholder, hint ("720 × 720 (10MB max)") |
| `--color-text-muted`     | `#737373` (est.) | Label grup sidebar ("My Pages")                              |
| `--color-icon`           | `#717171`  | Ikon nav tidak aktif                                                 |
| `--color-icon-active`    | `#000000`  | Ikon nav aktif, ikon toolbar editor, ikon upload (`#222222`)         |
| `--color-border`         | `#ececec`  | Divider section, border bawah header                                 |
| `--color-border-sidebar` | `#e0e0e0`  | Divider di bawah header sidebar                                      |
| `--color-border-input`   | `#e2e2e2`  | Border input teks                                                    |
| `--color-border-strong`  | `#dbdbdb`  | Border dashed dropzone, sisi bawah input (efek bayangan)             |
| `--color-border-editor`  | `#e1e1e1`  | Border editor rich text dan border nav item aktif                    |
| `--color-border-toolbar` | `#e8e8e8`  | Garis pemisah toolbar editor dengan area tulis                       |
| `--color-hover-subtle`   | `#f6f6f6`  | Latar tombol toolbar yang aktif (mis. **B**)                         |
| `--color-dropzone`       | `#f9f9f9`  | Latar dropzone (belang diagonal dengan `#fefefe`)                    |
| `--color-brand`          | `#1b1b1b`  | Latar logo organisasi (ikon centang putih)                           |

### 1.2 Aturan Pemakaian
- Desain **monokrom**: tidak ada warna aksen. Hierarki dibuat dari hitam, abu-abu, dan bayangan halus.
- Teks utama selalu hitam murni `#000000`; teks sekunder hanya satu tingkat abu-abu.
- Jangan menambahkan warna status (biru/hijau/merah) kecuali dibutuhkan untuk validasi, dan tetap dibuat sehalus mungkin.

---

## 2. Tipografi

| Properti        | Nilai                                                                 |
| --------------- | --------------------------------------------------------------------- |
| Font family     | **Inter**, fallback: `ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif` |
| Berat font      | **400 (Regular)** untuk hampir semua teks; nama organisasi boleh 500  |
| Rendering       | `-webkit-font-smoothing: antialiased`                                 |
| Letter spacing  | Normal (0)                                                            |

### 2.1 Skala Tipografi

| Peran                         | Ukuran | Line height | Warna            | Contoh dari referensi                       |
| ----------------------------- | ------ | ----------- | ---------------- | ------------------------------------------- |
| Judul halaman                 | 20px   | 28px        | `text`           | "Home"                                      |
| Judul section                 | 16px   | 24px        | `text`           | "Unique feature items", "Row section"       |
| Subjudul halaman              | 15px   | 22px        | `text-secondary` | "View and update content on your homepage"  |
| Label field                   | 15px   | 22px        | `text`           | "Left feature", "Title information"        |
| Teks input / placeholder      | 15px   | 22px        | `text` / `text-secondary` | "Enter short title"              |
| Item nav sidebar              | 15px   | 22px        | `text`           | "General", "Home", "About"                  |
| Nama organisasi (header)      | 15px   | 22px        | `text`           | "Pedicure practice"                         |
| Judul dropzone                | 15px   | 22px        | `text`           | "Drop your file here or browse"             |
| Hint dropzone                 | 13px   | 18px        | `text-secondary` | "720 × 720 (10MB max)"                      |
| Label grup sidebar            | 12px   | 16px        | `text-muted`     | "My Pages"                                  |

---

## 3. Layout Utama

```
┌──────────────┬───────────────────────────────────────────────┐
│ Sidebar      │ Header (kosong, tinggi 64px, border bawah)    │
│ 256px        ├───────────────────────────────────────────────┤
│ bg #f6f6f6   │ Konten (bg putih, padding kiri ±68px)         │
│              │  Judul halaman + subjudul                     │
│              │  ───────── divider ─────────                  │
│              │  Section 1 (judul + grid field)               │
│              │  ───────── divider ─────────                  │
│              │  Section 2 ...                                │
└──────────────┴───────────────────────────────────────────────┘
```

| Elemen                  | Nilai                                                                 |
| ----------------------- | --------------------------------------------------------------------- |
| Latar luar              | `#dbdbdb`; aplikasi tampil sebagai panel dengan jarak ±22px dari tepi atas dan kiri |
| Lebar sidebar           | **256px**, tanpa border kanan (batas hanya dari kontras `#f6f6f6` vs putih) |
| Tinggi header           | **64px** (sejajar dengan header sidebar), `border-bottom: 1px solid #ececec` |
| Padding kiri konten     | ≈ **68px**                                                            |
| Divider section         | `1px solid #ececec`, mulai dari tepi kiri konten hingga tepi kanan    |
| Padding atas konten     | ≈ 32px di bawah header sebelum judul halaman                          |
| Jarak subjudul → divider| ≈ 22px                                                                |
| Jarak divider → judul section | ≈ 36px                                                          |
| Jarak judul section → baris field | ≈ 34px                                                      |
| Jarak input → divider berikutnya | ≈ 32px                                                       |
| Jarak antar field (vertikal) | ≈ 30px                                                           |
| Gap antar kolom grid    | **24px**                                                              |
| Jumlah kolom            | 3 kolom sama lebar (terlihat di "Unique feature items" dan "Row section") |

---

## 4. Sidebar

### 4.1 Header Sidebar (Pemilih Organisasi)
- Tinggi 64px, `border-bottom: 1px solid #e0e0e0`, dengan inset ±10px di kiri/kanan.
- Padding kiri ≈ 22px.
- **Logo**: kotak 28 × 28px, `border-radius: 8px`, latar `#1b1b1b`, ikon centang/swoosh putih di tengah.
- Jarak logo → nama: 12px. Nama: 15px, hitam.
- **Ikon chevron naik-turun** (`chevrons-up-down`, 16px, hitam) rata kanan dengan padding kanan ≈ 22px.

### 4.2 Daftar Navigasi
| Properti                  | Nilai                                                         |
| ------------------------- | ------------------------------------------------------------- |
| Padding horizontal wadah  | ≈ 18px                                                        |
| Tinggi item               | **32px**                                                      |
| Jarak antar item (pitch)  | 38px (gap 6px)                                                |
| Padding horizontal item   | 16px                                                          |
| Jarak ikon → teks         | 12px                                                          |
| Ukuran ikon               | 16px (glyph solid/filled, bukan outline)                      |
| Ukuran teks               | 15px, hitam, berat 400                                        |
| Border radius             | 8px                                                           |

Susunan: grup pertama tanpa judul (General, Invoice), lalu label grup **"My Pages"** (12px, `#737373`) dengan jarak ≈ 46px dari item sebelumnya dan ≈ 37px ke item berikutnya (Home, About, Care tips, Specialisation, Administrative).

### 4.3 State Item Navigasi

| State        | Latar      | Border               | Bayangan                                              | Teks    | Ikon      |
| ------------ | ---------- | -------------------- | ----------------------------------------------------- | ------- | --------- |
| Default      | transparan | tidak ada            | tidak ada                                             | `#000`  | `#717171` |
| **Aktif**    | `#ffffff`  | `1px solid #e1e1e1`  | `0 1px 2px rgba(0,0,0,.12), 0 4px 8px -2px rgba(0,0,0,.04)` | `#000` | `#000` |
| Hover (usulan) | `#ffffff` opasitas 60% | — | — | `#000` | `#000` |

> Halaman aktif ditandai dengan **kapsul putih terangkat** (bukan garis atau warna aksen) dan ikon berubah dari abu-abu menjadi hitam.

---

## 5. Header Halaman

```html
<h1>Home</h1>                                   <!-- 20px / #000 -->
<p>View and update content on your homepage</p> <!-- 15px / #555 -->
<hr>                                            <!-- 1px #ececec -->
```

- Judul dan subjudul rata kiri, jarak antar keduanya ≈ 8px.
- Divider berada di bawah subjudul dan membentang penuh ke kanan.

---

## 6. Komponen Form

### 6.1 Section
- Judul section 16px, hitam.
- Setiap section dipisahkan divider `1px solid #ececec` dengan padding vertikal ±32px.

### 6.2 Label
- 15px, hitam, berat 400, rata kiri, di atas field.
- Jarak label → field: **12px**.

### 6.3 Text Input

| Properti        | Nilai                                                                      |
| --------------- | -------------------------------------------------------------------------- |
| Tinggi          | **40px**                                                                   |
| Lebar           | 100% kolom (±271px pada grid 3 kolom)                                      |
| Latar           | `#ffffff`                                                                  |
| Border          | `1px solid #e2e2e2`                                                        |
| Border radius   | **8px**                                                                    |
| Bayangan        | `0 1px 2px rgba(0,0,0,.06)` (sisi bawah tampak sedikit lebih tegas, ±`#dbdbdb`) |
| Padding         | `0 14px`                                                                   |
| Teks            | 15px, `#000`                                                               |
| Placeholder     | 15px, `#555555`, berat 400                                                 |
| Focus (usulan)  | border `#bdbdbd` + ring `0 0 0 3px rgba(0,0,0,.06)`, tanpa outline biru    |

Pola placeholder: kalimat instruksi pendek berawalan "Enter …" (contoh: *Enter left short feature*, *Enter short title*, *Enter brief content information*).

### 6.4 Dropzone Upload Gambar

| Properti        | Nilai                                                                      |
| --------------- | -------------------------------------------------------------------------- |
| Ukuran          | lebar penuh kolom (±369px) × tinggi **184px**                              |
| Border          | `1px dashed #dbdbdb`                                                       |
| Radius          | 8px                                                                        |
| Latar           | belang diagonal sangat halus, `repeating-linear-gradient(135deg, #f9f9f9 0 12px, #fefefe 12px 24px)` (est.) |
| Isi             | rata tengah (horizontal dan vertikal), tersusun vertikal                   |
| Tombol ikon     | kotak 40 × 40px, putih, `border: 1px solid #e7e7e7`, radius 8px, bayangan halus `0 1px 2px rgba(0,0,0,.08)`, ikon *upload* (panah naik dari tray) 18px, stroke `#222` |
| Judul           | 15px hitam, margin atas ≈ 16px dari tombol: "Drop your file here or browse" |
| Hint            | 13px `#555555`, margin atas ≈ 8px: "720 × 720 (10MB max)"                     |

### 6.5 Rich Text Editor

| Properti        | Nilai                                                                      |
| --------------- | -------------------------------------------------------------------------- |
| Ukuran          | lebar penuh kolom × tinggi **188px**                                       |
| Border          | `1px solid #e1e1e1`, radius 8px, latar putih                               |
| Toolbar         | tinggi ≈ 38px, `border-bottom: 1px solid #e8e8e8`                          |
| Isi toolbar     | 6 tombol tersebar rata (`justify-content: space-between`), padding horizontal ≈ 26px |
| Urutan tombol   | **B** (bold), *I* (italic), <u>U</u> (underline), rata kiri, rata tengah, rata kanan |
| Ikon toolbar    | 16px, hitam; bold/italic/underline berupa huruf                            |
| Tombol aktif    | kotak ≈ 28px, radius 6px, latar `#f6f6f6` (pada referensi **B** dalam state aktif) |
| Area tulis      | padding ≈ 14px, placeholder "Enter brief content information" 15px `#555555` |
| Resize handle   | grip titik-titik kecil di pojok kanan bawah (`resize: vertical`)           |

---

## 7. Ikon

- Gaya: **solid/filled** untuk navigasi sidebar (grid 4 titik, struk/receipt, rumah, orang, pensil, roket, buku); **outline tipis 1,5px** untuk ikon aksi (upload, chevrons-up-down, rata teks).
- Ukuran standar 16px; ukuran tombol ikon 40px.
- Rekomendasi pustaka: **Phosphor Icons** (varian *fill* untuk nav, *regular* untuk aksi) atau **Heroicons** (solid & outline).

---

## 8. Bayangan dan Radius

| Token           | Nilai                                                      | Dipakai untuk                  |
| --------------- | ---------------------------------------------------------- | ------------------------------ |
| `--radius-md`   | `8px`                                                      | Input, dropzone, editor, nav item, logo, tombol ikon |
| `--radius-sm`   | `6px`                                                      | Tombol kecil di toolbar        |
| `--shadow-xs`   | `0 1px 2px rgba(0,0,0,.06)`                                | Input teks                     |
| `--shadow-sm`   | `0 1px 2px rgba(0,0,0,.12), 0 4px 8px -2px rgba(0,0,0,.04)`| Nav item aktif                 |

Tidak ada gradien, blur latar, atau bayangan besar; kesan visual: **flat, bersih, minimalis**.

---

## 9. CSS Variables (Siap Pakai)

```css
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap");

:root {
  /* Color */
  --color-canvas: #dbdbdb;
  --color-surface: #ffffff;
  --color-sidebar: #f6f6f6;
  --color-text: #000000;
  --color-text-secondary: #555555;
  --color-text-muted: #737373;
  --color-icon: #717171;
  --color-border: #ececec;
  --color-border-sidebar: #e0e0e0;
  --color-border-input: #e2e2e2;
  --color-border-strong: #dbdbdb;
  --color-border-editor: #e1e1e1;
  --color-border-toolbar: #e8e8e8;
  --color-subtle: #f6f6f6;
  --color-dropzone: #f9f9f9;
  --color-brand: #1b1b1b;

  /* Typography */
  --font-sans: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --text-title: 20px;
  --text-section: 16px;
  --text-base: 15px;
  --text-hint: 13px;
  --text-group: 12px;

  /* Layout */
  --sidebar-width: 256px;
  --header-height: 64px;
  --content-padding-left: 68px;
  --grid-gap: 24px;
  --control-height: 40px;

  /* Shape */
  --radius-md: 8px;
  --radius-sm: 6px;
  --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.06);
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.12), 0 4px 8px -2px rgba(0, 0, 0, 0.04);
}

html, body {
  font-family: var(--font-sans);
  font-size: var(--text-base);
  color: var(--color-text);
  background: var(--color-canvas);
  -webkit-font-smoothing: antialiased;
}
```

## 10. Tailwind Config (Opsional)

```js
// tailwind.config.js
export default {
  content: ["./resources/**/*.blade.php", "./resources/**/*.js"],
  theme: {
    extend: {
      fontFamily: { sans: ["Inter", "ui-sans-serif", "system-ui", "sans-serif"] },
      colors: {
        canvas: "#dbdbdb",
        sidebar: "#f6f6f6",
        ink: { DEFAULT: "#000000", secondary: "#555555", muted: "#737373", icon: "#717171" },
        line: { DEFAULT: "#ececec", sidebar: "#e0e0e0", input: "#e2e2e2", strong: "#dbdbdb", editor: "#e1e1e1", toolbar: "#e8e8e8" },
        brand: "#1b1b1b",
      },
      fontSize: {
        title: ["20px", "28px"],
        section: ["16px", "24px"],
        base: ["15px", "22px"],
        hint: ["13px", "18px"],
        group: ["12px", "16px"],
      },
      spacing: { sidebar: "256px", header: "64px" },
      borderRadius: { DEFAULT: "8px" },
      boxShadow: {
        xs: "0 1px 2px rgba(0,0,0,.06)",
        nav: "0 1px 2px rgba(0,0,0,.12), 0 4px 8px -2px rgba(0,0,0,.04)",
      },
    },
  },
};
```

## 11. Contoh Komponen Blade

### Nav item
```blade
{{-- resources/views/components/nav-item.blade.php --}}
@props(['href', 'active' => false, 'icon'])

<a href="{{ $href }}"
   class="flex h-8 items-center gap-3 rounded-lg border px-4 text-base text-ink
          {{ $active ? 'border-line-editor bg-white shadow-nav' : 'border-transparent' }}">
    <span class="{{ $active ? 'text-ink' : 'text-ink-icon' }}">{{ $icon }}</span>
    {{ $slot }}
</a>
```

### Text input
```blade
{{-- resources/views/components/input.blade.php --}}
@props(['label', 'name', 'placeholder' => ''])

<div class="flex flex-col gap-3">
    <label for="{{ $name }}" class="text-base text-ink">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" placeholder="{{ $placeholder }}"
           class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px]
                  text-base text-ink shadow-xs placeholder:text-ink-secondary
                  focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
</div>
```

### Dropzone
```blade
<div class="flex h-[184px] flex-col items-center justify-center rounded-lg border border-dashed border-line-strong"
     style="background: repeating-linear-gradient(135deg, #f9f9f9 0 12px, #fefefe 12px 24px)">
    <button type="button"
            class="mb-4 flex size-10 items-center justify-center rounded-lg border border-[#e7e7e7] bg-white shadow-xs">
        {{-- icon upload --}}
    </button>
    <p class="text-base text-ink">Drop your file here or browse</p>
    <p class="mt-2 text-hint text-ink-secondary">720 × 720 (10MB max)</p>
</div>
```

### Section
```blade
<section class="border-b border-line py-8">
    <h2 class="mb-8 text-section text-ink">Unique feature items</h2>
    <div class="grid grid-cols-3 gap-6">
        {{ $slot }}
    </div>
</section>
```

---

## 12. Penerapan pada Library Management System

Agar sistem perpustakaan memakai gaya yang sama persis, pemetaan kerangkanya:

| Elemen referensi                  | Penerapan di aplikasi                                                  |
| --------------------------------- | ---------------------------------------------------------------------- |
| Header sidebar "Pedicure practice"| Nama aplikasi/perpustakaan + logo 28px                                 |
| Grup tanpa judul                  | Dashboard / Akun                                                       |
| Grup "My Pages"                   | Menu fitur: **Employee** (Books, Members, Borrow Book, Return Book, Borrow History) / **Member** (Book Collection, My Borrowed Books) |
| Halaman "Home" + subjudul         | Judul halaman + deskripsi singkat tiap fitur                           |
| Section + grid 3 kolom            | Form tambah/edit buku (Title, Author, Publisher, Page, ISBN) dan form Borrow/Return |
| Input teks                        | Semua field form (placeholder berawalan "Enter …")                     |
| Rich text editor & dropzone       | Opsional (mis. deskripsi atau sampul buku) bila dibutuhkan             |

Untuk tabel (daftar buku, member, riwayat) yang tidak ada di referensi, gunakan token yang sama: latar putih, border `#ececec` per baris, header kolom 12px `#737373`, teks 15px hitam, tanpa warna aksen, dan badge status berupa pil netral (mis. `isAvailable` putih ber-border `#e2e2e2`, `isBorrowed` latar `#f6f6f6`).

---

## 13. Checklist Kecocokan Visual

- [ ] Font Inter, berat 400, ukuran 20 / 16 / 15 / 13 / 12px
- [ ] Sidebar 256px, latar `#f6f6f6`, tanpa border kanan
- [ ] Header 64px dengan border bawah `#ececec`
- [ ] Nav aktif: kapsul putih, border `#e1e1e1`, bayangan halus, ikon hitam
- [ ] Nav tidak aktif: tanpa latar, ikon `#717171`
- [ ] Input 40px, radius 8px, border `#e2e2e2`, placeholder abu-abu
- [ ] Dropzone: dashed `#dbdbdb`, latar belang halus, tombol upload 40px
- [ ] Editor: border `#e1e1e1`, toolbar 6 tombol dengan divider `#e8e8e8`
- [ ] Grid 3 kolom, gap 24px; divider antar section `#ececec`
- [ ] Tidak ada warna aksen, gradien, atau bayangan besar
