<div align="center">

# 🍳 DapurIn — Platform Resep Masakan

<p>Platform berbagi dan mengelola resep masakan berbasis <strong>PHP Native</strong> + <strong>MySQL</strong></p>

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)

[![License](https://img.shields.io/badge/License-MIT-2d6a4f?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Active-2d6a4f?style=flat-square)]()
[![Live](https://img.shields.io/badge/Live-dapurin.free.nf-40916c?style=flat-square&logo=googlechrome&logoColor=white)](https://dapurin.free.nf)
[![GitHub](https://img.shields.io/badge/GitHub-SitiFatimahNA-181717?style=flat-square&logo=github)](https://github.com/SitiFatimahNA/Resep-Masakan)

</div>

---

## 📌 Tentang Project

**DapurIn** adalah aplikasi web full-stack yang dibangun menggunakan PHP Native tanpa framework. Platform ini memungkinkan pengguna untuk berbagi, menemukan, dan mengelola resep masakan dari berbagai kategori — mulai dari masakan sehari-hari hingga dessert istimewa.

Tampilan didesain dengan tema **hijau elegan** dan mendukung **mobile responsive** penuh — nyaman diakses dari HP maupun desktop.

> 🌐 **Live:** [dapurin.free.nf](https://dapurin.free.nf)

---

## ✨ Fitur Lengkap

<table>
<tr>
<td width="33%">

### 🌐 Publik
- Hero section modern dengan border-radius elegan
- Daftar resep + filter, pencarian, pagination
- Detail resep: bahan checklist, langkah, nutrisi, alat masak
- Kategori & jenis masakan dinamis
- Resep terpopuler (podium top 3)
- Foto profil pembuat di card resep

</td>
<td width="33%">

### 👤 User (Login)
- Tambah & edit resep (bahan, langkah, alat, nutrisi)
- Status **Publik / Draft**
- Rating bintang 1–5
- Komentar + reply bertingkat
- **Edit & hapus komentar sendiri** (AJAX)
- Simpan & hapus resep favorit (AJAX)
- Halaman resep saya & favorit
- Edit profil & foto + ganti password

</td>
<td width="33%">

### 🔧 Admin
- Dashboard + bar chart statistik 6 bulan
- Kelola resep (CRUD + toggle publik/draft)
- Kelola user (role admin/user)
- Kelola kategori & jenis masakan
- Moderasi komentar (aktif/nonaktif)
- Laporan resep + **Export CSV** + **Cetak PDF**
- Panel responsif (desktop & mobile)

</td>
</tr>
</table>

---

## 📱 Responsif Mobile

Tampilan dioptimalkan untuk semua ukuran layar dengan pendekatan **dual view**:

```
Desktop  ████████████████████  1200px+   → sidebar + tabel + grid 3 kolom
Tablet   █████████████         1024px+   → sidebar + tabel compact
Mobile   ████████              < 1024px  → hamburger menu + card layout
```

Fitur mobile:
- Navbar: search bar + tombol Masuk/Daftar proporsional
- Admin: sidebar slide-in dengan overlay
- Card resep: 2 kolom dengan info waktu di thumbnail
- Filter bar: stack vertikal, tombol full width
- Komentar: edit & hapus inline tanpa reload

---

## 🛡️ Keamanan

| Aspek | Implementasi |
|-------|-------------|
| Password | `password_hash()` + `password_verify()` bcrypt |
| Query Database | Prepared Statement `mysqli_prepare()` |
| Validasi Input | Server-side validation semua form |
| Akses Halaman | Role-based access control (admin / user) |
| Upload File | Validasi ekstensi + ukuran (maks 2MB) |
| Session | PHP session dengan role check tiap halaman |
| AJAX | Verifikasi ownership sebelum edit/hapus komentar |

---

## 🗄️ Database

**Nama:** `resep_masakan` &nbsp;|&nbsp; **Total Tabel:** 12

```
┌──────────────────┬────────────────────────────────────────────┐
│ Tabel            │ Keterangan                                 │
├──────────────────┼────────────────────────────────────────────┤
│ users            │ Data pengguna & autentikasi                │
│ kategori         │ Kategori resep (Ayam, Ikan, Sapi, dll)     │
│ jenis_masakan    │ Jenis sajian (Sarapan, Makan Siang, dll)   │
│ resep            │ Data resep utama                           │
│ resep_kategori   │ Relasi many-to-many resep ↔ kategori       │
│ bahan_resep      │ Bahan-bahan tiap resep                     │
│ langkah_resep    │ Langkah memasak + foto langkah             │
│ alat_masak       │ Daftar alat masak tiap resep               │
│ nutrisi          │ Info nutrisi per porsi (kalori, protein…)  │
│ komentar         │ Komentar + reply bertingkat (parent_id)    │
│ rating           │ Rating bintang 1–5 per user per resep      │
│ favorit          │ Resep yang disimpan user                   │
└──────────────────┴────────────────────────────────────────────┘
```

---

## 🚀 Instalasi Lokal

### Prasyarat
- [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL + Apache)
- [Git](https://git-scm.com/)

### Langkah

**1. Clone repository**
```bash
cd C:\xampp\htdocs
git clone https://github.com/SitiFatimahNA/Resep-Masakan.git resep-masakan
```

**2. Import database**
```
1. Buka http://localhost/phpmyadmin
2. Buat database: resep_masakan
3. Import file: config/database.sql
```

**3. Buat file koneksi** `config/koneksi.php`
```php
<?php
date_default_timezone_set('Asia/Jakarta');
$koneksi = mysqli_connect('localhost', 'root', '', 'resep_masakan');
mysqli_set_charset($koneksi, 'utf8mb4');
mysqli_query($koneksi, "SET time_zone = '+07:00'");
```

**4. Jalankan**
```
http://localhost/resep-masakan/
```

---

## 👤 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| 🔴 Admin | `admin@resepmasakan.com` | `password` |
| 🟢 User | Daftar via `/register.php` | — |

---

## 📁 Struktur Folder

```
resep-masakan/
│
├── 📂 admin/                    # Panel Admin
│   ├── 📂 includes/
│   │   ├── admin-header.php     # Sidebar, topbar, CSS admin
│   │   └── admin-footer.php
│   ├── dashboard.php            # Statistik & chart
│   ├── kelola-resep.php         # CRUD resep + dual view
│   ├── kelola-user.php          # Manajemen user
│   ├── kelola-kategori.php      # CRUD kategori & jenis
│   ├── kelola-komentar.php      # Moderasi komentar
│   └── laporan.php              # Filter + Export CSV + Cetak PDF
│
├── 📂 assets/css/style.css      # Stylesheet utama (tema hijau elegan)
│
├── 📂 config/
│   ├── database.sql             # Skema database
│   └── koneksi.php              # Koneksi DB (tidak di-push ke Git)
│
├── 📂 includes/
│   ├── header.php               # Navbar responsif
│   └── footer.php               # Footer + kategori dinamis + JS global
│
├── 📂 uploads/                  # File upload user
│
├── 📂 user/                     # Fitur user login
│   ├── tambah-resep.php
│   ├── edit-resep.php
│   ├── resep-saya.php
│   ├── favorit.php
│   ├── profil.php
│   ├── ajax-favorit.php         # AJAX toggle favorit
│   └── ajax-komentar.php        # AJAX edit & hapus komentar
│
├── home.php                     # Beranda
├── resep.php                    # Daftar resep + filter
├── detail-resep.php             # Detail resep + komentar
├── kategori.php                 # Semua kategori
├── populer.php                  # Resep terpopuler
├── login.php
├── register.php
└── logout.php
```

---

## 📸 Screenshots

### 🌐 Halaman Publik

| Beranda | Semua Resep |
|---------|-------------|
| ![Beranda](docs/screenshots/home.png) | ![Resep](docs/screenshots/resep.png) |

| Detail Resep | Kategori |
|-------------|----------|
| ![Detail](docs/screenshots/detail-resep.png) | ![Kategori](docs/screenshots/kategori.png) |

| Terpopuler |
|-----------|
| ![Populer](docs/screenshots/populer.png) |

### 🔐 Autentikasi

| Login | Register |
|-------|----------|
| ![Login](docs/screenshots/login.png) | ![Register](docs/screenshots/register.png) |

### 👤 User

| Resep Saya | Tambah Resep |
|-----------|-------------|
| ![Resep Saya](docs/screenshots/resep-saya.png) | ![Tambah Resep](docs/screenshots/tambah-resep.png) |

| Favorit | Profil |
|---------|--------|
| ![Favorit](docs/screenshots/favorit.png) | ![Profil](docs/screenshots/profil.png) |

### 🔧 Admin

| Dashboard | Kelola Resep |
|-----------|-------------|
| ![Dashboard](docs/screenshots/dashboard.png) | ![Kelola Resep](docs/screenshots/kelola-resep.png) |

| Kelola Kategori & Jenis | Kelola Komentar |
|------------------------|----------------|
| ![Kategori](docs/screenshots/kelola-kategori.png) | ![Komentar](docs/screenshots/kelola-komentar.png) |

| Kelola User | Laporan & Export |
|------------|-----------------|
| ![User](docs/screenshots/kelola-user.png) | ![Laporan](docs/screenshots/laporan.png) |

---

| Layer | Teknologi |
|-------|-----------|
| **Backend** | PHP 8.x Native |
| **Database** | MySQL 8.0 + mysqli Prepared Statements |
| **Frontend** | HTML5, CSS3, JavaScript Vanilla |
| **Icon** | Font Awesome 6.5 |
| **Font** | Playfair Display + Poppins + Plus Jakarta Sans |
| **Hosting** | InfinityFree |

---

## 🗂️ ERD (Entity Relationship Diagram)

```mermaid
erDiagram
    users {
        int id PK
        varchar nama
        varchar email
        varchar password
        enum role
        varchar foto
        text bio
        timestamp created_at
    }
    kategori {
        int id PK
        varchar nama_kategori
        varchar slug
        varchar icon
    }
    jenis_masakan {
        int id PK
        varchar nama_jenis
        varchar slug
    }
    resep {
        int id PK
        int user_id FK
        int id_kategori FK
        int id_jenis FK
        varchar judul
        varchar slug
        text deskripsi
        enum tingkat_kesulitan
        int waktu_masak
        int porsi
        varchar thumbnail
        enum status
        int views
        timestamp created_at
    }
    resep_kategori {
        int id_resep FK
        int id_kategori FK
    }
    bahan_resep {
        int id PK
        int id_resep FK
        varchar nama_bahan
        varchar jumlah
        varchar satuan
    }
    langkah_resep {
        int id PK
        int id_resep FK
        int urutan
        text instruksi
        varchar foto_langkah
    }
    alat_masak {
        int id PK
        int id_resep FK
        varchar nama_alat
    }
    nutrisi {
        int id PK
        int id_resep FK
        float kalori
        float protein
        float karbohidrat
        float lemak
        float serat
    }
    komentar {
        int id PK
        int id_resep FK
        int id_user FK
        int parent_id FK
        text komentar
        enum status
        timestamp created_at
    }
    rating {
        int id PK
        int id_resep FK
        int id_user FK
        tinyint nilai
        timestamp created_at
    }
    favorit {
        int id PK
        int id_resep FK
        int id_user FK
        timestamp created_at
    }

    users ||--o{ resep : "membuat"
    users ||--o{ komentar : "menulis"
    users ||--o{ rating : "memberi"
    users ||--o{ favorit : "menyimpan"
    resep ||--o{ bahan_resep : "memiliki"
    resep ||--o{ langkah_resep : "memiliki"
    resep ||--o{ alat_masak : "membutuhkan"
    resep ||--o| nutrisi : "memiliki"
    resep ||--o{ komentar : "menerima"
    resep ||--o{ rating : "menerima"
    resep ||--o{ favorit : "disimpan di"
    resep ||--o{ resep_kategori : "termasuk"
    kategori ||--o{ resep : "mengkategorikan"
    kategori ||--o{ resep_kategori : "dimiliki"
    jenis_masakan ||--o{ resep : "mengklasifikasi"
    komentar ||--o{ komentar : "dibalas"
```

---

## 📖 Panduan Penggunaan

### 🌐 Pengunjung (Tanpa Login)

**Menjelajah Resep**
1. Buka halaman utama → tampil resep terbaru & terpopuler
2. Klik **Semua Resep** di navbar untuk melihat seluruh resep
3. Gunakan **Filter Resep** untuk menyaring berdasarkan kategori, jenis, kesulitan, atau waktu masak
4. Ketik nama resep di kolom **Cari** lalu klik **Terapkan Filter**
5. Klik kartu resep untuk melihat detail lengkap (bahan, langkah, nutrisi, alat masak)

**Melihat Resep Terpopuler**
1. Klik menu **Terpopuler** di navbar
2. Lihat podium top 3 dan daftar resep dengan views & rating tertinggi

---

### 👤 User (Setelah Login)

**Registrasi Akun**
1. Klik tombol **Daftar** di navbar
2. Isi nama lengkap, email, password (minimal 6 karakter), konfirmasi password
3. Klik **Daftar Sekarang** → otomatis login dan masuk ke beranda

**Login**
1. Klik tombol **Masuk** di navbar
2. Isi email dan password
3. Klik **Masuk** → diarahkan ke beranda

**Menambah Resep Baru**
1. Setelah login, klik **+ Tambah Resep** di navbar atau topbar admin
2. Isi informasi dasar: judul, deskripsi, kategori, jenis, tingkat kesulitan, waktu masak, porsi
3. Upload foto thumbnail resep (maks 2MB, format JPG/PNG/WEBP)
4. Tambahkan **bahan** satu per satu: nama bahan, jumlah, satuan → klik **+ Tambah Bahan**
5. Tambahkan **langkah memasak**: tulis instruksi, bisa upload foto tiap langkah → klik **+ Tambah Langkah**
6. (Opsional) Tambahkan alat masak dan informasi nutrisi
7. Pilih status: **Publik** (tampil di beranda) atau **Draft** (tersimpan, tidak tampil)
8. Klik **Simpan Resep**

**Mengedit & Menghapus Resep Sendiri**
1. Klik **Resep Saya** di menu profil
2. Klik ikon ✏️ untuk edit atau 🗑️ untuk hapus resep

**Memberi Rating**
1. Buka halaman detail resep
2. Klik bintang 1–5 di bagian bawah judul resep
3. Rating tersimpan otomatis (bisa diubah kapan saja)

**Berkomentar**
1. Scroll ke bawah halaman detail resep
2. Tulis komentar di kolom yang tersedia → klik **Kirim**
3. Untuk membalas komentar orang lain, klik **Balas** di bawah komentar tersebut
4. Komentar sendiri bisa diedit (klik ✏️) atau dihapus (klik 🗑️) tanpa reload halaman

**Menyimpan Resep Favorit**
1. Di halaman detail resep, klik tombol **♡ Simpan** di bagian atas
2. Resep tersimpan di halaman **Favorit** (menu profil)
3. Klik tombol yang sama lagi untuk menghapus dari favorit

**Mengedit Profil**
1. Klik foto profil / nama di navbar → pilih **Profil**
2. Edit nama, bio, dan foto profil
3. Untuk ganti password: isi password lama, password baru, konfirmasi → klik **Simpan**

---

### 🔧 Admin

**Login Admin**
1. Buka `/login.php`
2. Gunakan akun admin: `admin@resepmasakan.com` / `password`
3. Otomatis diarahkan ke **Dashboard Admin**

**Dashboard**
- Lihat statistik: total resep, member, komentar, views, rating, resep publik
- Bar chart resep per bulan (6 bulan terakhir)
- Daftar resep terpopuler

**Kelola Resep**
1. Klik **Kelola Resep** di sidebar
2. Gunakan filter untuk mencari resep berdasarkan judul, status, atau kategori
3. Klik ikon 👁️ untuk lihat detail, ✏️ untuk edit, 🗑️ untuk hapus
4. Klik tombol toggle **Publik/Draft** untuk mengubah status resep langsung dari tabel

**Kelola Kategori & Jenis**
1. Klik **Kategori & Jenis** di sidebar
2. Tambah kategori baru: isi nama → klik **Tambah**
3. Edit atau hapus kategori/jenis yang sudah ada

**Moderasi Komentar**
1. Klik **Moderasi Komentar** di sidebar
2. Filter berdasarkan status (Aktif / Nonaktif)
3. Klik toggle untuk mengaktifkan atau menonaktifkan komentar
4. Klik 🗑️ untuk menghapus komentar permanen

**Kelola User**
1. Klik **Kelola User** di sidebar
2. Lihat daftar semua user beserta jumlah resep yang dibuat
3. Ubah role user (admin/user) atau hapus akun

**Laporan & Export**
1. Klik **Laporan & Export** di sidebar
2. Set rentang tanggal dan filter status → klik **Terapkan**
3. Klik **Export CSV** untuk download data ke Excel/Google Sheets
4. Klik **Cetak PDF** untuk membuka dialog print browser

---

## 📊 Fitur Laporan Admin

- 🔍 Filter rentang tanggal & status (Publik / Draft)
- 📋 Tabel detail: judul, pembuat, kategori, views, rating, komentar
- 📥 **Export CSV** — langsung dibuka di Excel / Google Sheets
- 🖨️ **Cetak PDF** — via browser print dialog

---

<div align="center">

**Dibuat oleh Siti Fatimah Nur Az-Zahra**

© 2026 DapurIn — All rights reserved.

</div>
