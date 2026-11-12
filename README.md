<div align="center">

# 🍳 DapurIn — Sistem Informasi Resep Masakan

<p>Platform berbagi dan mengelola resep masakan berbasis <strong>PHP Native</strong> + <strong>MySQL</strong></p>

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://apachefriends.org)

[![License](https://img.shields.io/badge/License-MIT-2d6a4f?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Completed-2d6a4f?style=flat-square)]()
[![GitHub](https://img.shields.io/badge/GitHub-SitiFatimahNA-181717?style=flat-square&logo=github)](https://github.com/SitiFatimahNA/Resep-Masakan)

</div>

---

## 📌 Tentang Project

**DapurIn** adalah aplikasi web full-stack yang dibangun menggunakan PHP Native tanpa framework. Platform ini memungkinkan pengguna untuk berbagi, menemukan, dan mengelola resep masakan dari berbagai kategori — mulai dari masakan sehari-hari hingga dessert istimewa.

Tampilan didesain dengan tema **hijau elegan** dan mendukung **mobile responsive** penuh — nyaman diakses dari HP maupun desktop.

> Project ini dikerjakan sebagai tugas akhir mata pelajaran Pemrograman Web.

---

## ✨ Fitur Lengkap

<table>
<tr>
<td width="33%">

### 🌐 Publik
- Hero section modern + animasi float
- Daftar resep dengan filter, pencarian, pagination
- Detail resep: bahan, langkah, nutrisi, alat masak
- Kategori & jenis masakan
- Resep terpopuler (podium top 3)
- Foto profil pembuat di card resep

</td>
<td width="33%">

### 👤 User (Login)
- Tambah resep (bahan, langkah, alat, nutrisi)
- Edit & hapus resep sendiri
- Status **Publik / Draft**
- Rating bintang 1–5
- Komentar + reply bertingkat
- Simpan resep ke favorit
- Edit profil & foto (klik foto langsung)
- Ganti password

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

Tampilan dioptimalkan untuk semua ukuran layar dengan pendekatan **dual view** (tabel di desktop, card di mobile):

```
Desktop  ████████████████████  1200px+   → sidebar + tabel + grid 3 kolom
Tablet   █████████████         768px+    → sidebar + tabel compact
Mobile   ████████              < 768px   → hamburger menu + card layout
```

Fitur mobile khusus:
- Navbar: search bar langsung + avatar dropdown toggle tap
- Admin: sidebar slide-in dengan overlay
- Card resep: 2 kolom dengan foto profil pembuat
- Detail resep: tombol Simpan/Edit tidak tertutup wave

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
│ komentar         │ Komentar + reply bertingkat                │
│ rating           │ Rating bintang 1–5 per user per resep      │
│ favorit          │ Resep yang disimpan user                   │
└──────────────────┴────────────────────────────────────────────┘
```

---

## 🚀 Instalasi & Menjalankan

### Prasyarat

- [XAMPP](https://www.apachefriends.org/) (PHP 8.x + MySQL + Apache)
- [Git](https://git-scm.com/)

### Langkah Instalasi

**1. Clone repository**

```bash
cd C:\xampp\htdocs
git clone https://github.com/SitiFatimahNA/Resep-Masakan.git resep-masakan
```

**2. Import database**

```
1. Buka http://localhost/phpmyadmin
2. Klik "New" → buat database: resep_masakan
3. Pilih tab "Import" → pilih file: config/database.sql
4. Klik "Go"
```

**3. Konfigurasi koneksi**

Buat file `config/koneksi.php`:

```php
<?php
$koneksi = mysqli_connect('localhost', 'root', '', 'resep_masakan');
if (!$koneksi) die('Koneksi gagal: ' . mysqli_connect_error());
mysqli_set_charset($koneksi, 'utf8mb4');
```

**4. Jalankan aplikasi**

```
1. Buka XAMPP Control Panel → Start Apache & MySQL
2. Buka browser → http://localhost/resep-masakan/
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
│   ├── 📂 includes/             # Layout admin (header & footer)
│   │   ├── admin-header.php     # Sidebar, topbar, CSS admin
│   │   └── admin-footer.php     # Closing tags + JS
│   ├── dashboard.php            # Statistik & chart
│   ├── kelola-resep.php         # CRUD resep + dual view
│   ├── kelola-user.php          # Manajemen user + dual view
│   ├── kelola-kategori.php      # CRUD kategori & jenis
│   ├── kelola-komentar.php      # Moderasi komentar + dual view
│   └── laporan.php              # Filter + Export CSV + Cetak PDF
│
├── 📂 assets/
│   ├── 📂 css/style.css         # Stylesheet utama (tema hijau elegan)
│   └── 📂 images/               # Gambar default & ikon
│
├── 📂 config/
│   ├── database.sql             # Skema database + data awal
│   └── koneksi.php              # Koneksi DB (tidak di-push ke Git)
│
├── 📂 includes/
│   ├── header.php               # Navbar responsif + search mobile
│   └── footer.php               # Footer + JavaScript global
│
├── 📂 uploads/
│   ├── resep/                   # Thumbnail resep
│   ├── profil/                  # Foto profil user
│   └── langkah/                 # Foto langkah memasak
│
├── 📂 user/                     # Fitur khusus user login
│   ├── tambah-resep.php         # Form tambah resep (bahan dinamis)
│   ├── edit-resep.php           # Form edit resep
│   ├── hapus-resep.php          # Hapus resep
│   ├── resep-saya.php           # Daftar resep milik user
│   ├── favorit.php              # Daftar resep favorit
│   ├── profil.php               # Edit profil & ganti password
│   └── ajax-favorit.php         # Handler AJAX toggle favorit
│
├── home.php                     # Beranda utama
├── resep.php                    # Daftar semua resep + filter
├── detail-resep.php             # Detail resep lengkap
├── kategori.php                 # Semua kategori
├── populer.php                  # Resep terpopuler (podium top 3)
├── login.php                    # Halaman login
├── register.php                 # Halaman daftar akun
└── logout.php                   # Proses logout
```

---

## 🛠️ Tech Stack

<div align="center">

| Layer | Teknologi |
|-------|-----------|
| **Backend** | PHP 8.x Native (tanpa framework) |
| **Database** | MySQL 8.0 + mysqli |
| **Frontend** | HTML5, CSS3, JavaScript Vanilla |
| **Icon** | Font Awesome 6.5 |
| **Font** | Playfair Display + Poppins + Plus Jakarta Sans |
| **Server** | Apache via XAMPP |

</div>

---

## 📊 Fitur Laporan Admin

- 🔍 Filter rentang tanggal & status (Publik / Draft)
- 📋 Tabel detail: judul, pembuat, kategori, views, rating, komentar
- 📥 **Export CSV** — langsung dibuka di Excel / Google Sheets
- 🖨️ **Cetak PDF** — via browser print dialog (`Ctrl+P`)

---

## 📸 Screenshot

> *Screenshot akan ditambahkan setelah proses demo selesai.*

---

## 👩‍💻 Pembuat

<div align="center">

| | |
|:--:|:--|
| **Nama** | Siti Fatimah Nur Az-Zahra |
| **Project** | DapurIn — Sistem Informasi Resep Masakan |
| **Stack** | PHP Native + MySQL + Vanilla JS |
| **Repository** | [github.com/SitiFatimahNA/Resep-Masakan](https://github.com/SitiFatimahNA/Resep-Masakan) |

<br>

*Dibuat dengan dedikasi untuk tugas akhir Pemrograman Web* 🌿

</div>

---

<div align="center">
<sub>© 2026 Siti Fatimah Nur Az-Zahra — DapurIn</sub>
</div>
