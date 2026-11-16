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

## 🛠️ Tech Stack

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
