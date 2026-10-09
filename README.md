<div align="center">

# 🍳 Sistem Informasi Resep Masakan

<p>Platform berbagi dan mengelola resep masakan berbasis <strong>PHP Native</strong> + <strong>MySQL</strong></p>

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://apachefriends.org)

[![License](https://img.shields.io/badge/License-MIT-2d6a4f?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Completed-2d6a4f?style=flat-square)]()
[![GitHub repo](https://img.shields.io/badge/GitHub-SitiFatimahNA-181717?style=flat-square&logo=github)](https://github.com/SitiFatimahNA/Resep-Masakan)

</div>

---

## 📌 Tentang Project

**Sistem Informasi Resep Masakan** adalah aplikasi web full-stack yang dibangun menggunakan PHP Native tanpa framework. Platform ini memungkinkan pengguna untuk berbagi, menemukan, dan mengelola resep masakan dari berbagai kategori — mulai dari masakan sehari-hari hingga dessert istimewa.

> Project ini dikerjakan sebagai tugas akhir mata pelajaran Pemrograman Web.

---

## ✨ Fitur

<table>
<tr>
<td width="33%">

### 🌐 Publik
- Hero section modern + animasi
- Daftar resep + filter & pencarian
- Detail resep lengkap
- Halaman kategori & jenis
- Resep terpopuler (podium top 3)

</td>
<td width="33%">

### 👤 User (Login)
- Tambah resep (bahan & langkah dinamis)
- Edit & hapus resep sendiri
- Rating bintang 1–5
- Komentar pada resep
- Simpan resep favorit
- Edit profil & ganti password

</td>
<td width="33%">

### 🔧 Admin
- Dashboard + bar chart statistik
- Kelola resep (CRUD + toggle status)
- Kelola user (role management)
- Kelola kategori & jenis masakan
- Moderasi komentar
- Laporan + **Export CSV** + **Cetak PDF**

</td>
</tr>
</table>

---

## 🛡️ Keamanan

| Aspek | Implementasi |
|-------|-------------|
| Password | `password_hash()` bcrypt |
| Query Database | Prepared Statement mysqli |
| Validasi Input | Server-side validation |
| Akses Halaman | Role-based access control (admin/user) |
| Upload File | Validasi tipe & ukuran file |

---

## 🗄️ Database

**Nama:** `resep_masakan` &nbsp;|&nbsp; **Total Tabel:** 9

```
┌─────────────────────────────────────────────────────────┐
│                    resep_masakan                        │
├──────────────┬──────────────────────────────────────────┤
│ users        │ Data pengguna & autentikasi              │
│ kategori     │ Kategori bahan (Ayam, Ikan, Sapi, dll)   │
│ jenis_masakan│ Jenis sajian (Sarapan, Makan Siang, dll) │
│ resep        │ Data resep utama                         │
│ bahan_resep  │ Bahan-bahan tiap resep                   │
│ langkah_resep│ Langkah memasak tiap resep               │
│ komentar     │ Komentar user pada resep                 │
│ rating       │ Rating bintang (1–5) per user per resep  │
│ favorit      │ Resep yang disimpan user                 │
└──────────────┴──────────────────────────────────────────┘
```

### ERD — Relasi Antar Tabel

```
users ─────────────────────────────────────────┐
  │                                             │
  └──< resep ──< bahan_resep                    │
         │                                      │
         ├──< langkah_resep                     │
         ├──< komentar >──────────────────── users
         ├──< rating   >──────────────────── users
         ├──< favorit  >──────────────────── users
         ├──> kategori
         └──> jenis_masakan
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
cd resep-masakan
```

**2. Import database**

```
1. Buka http://localhost/phpmyadmin
2. Klik "New" → buat database: resep_masakan
3. Pilih tab "Import" → pilih file config/database.sql
4. Klik "Go"
```

**3. Konfigurasi koneksi**

```bash
# Salin file konfigurasi contoh
cp config/koneksi.example.php config/koneksi.php
```

Edit `config/koneksi.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // sesuaikan username
define('DB_PASS', '');           // sesuaikan password
define('DB_NAME', 'resep_masakan');
```

**4. Jalankan aplikasi**

```
1. Buka XAMPP Control Panel
2. Start Apache dan MySQL
3. Buka browser → http://localhost/resep-masakan/
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
│   ├── dashboard.php            # Statistik & chart
│   ├── kelola-resep.php         # CRUD resep
│   ├── kelola-user.php          # Manajemen user
│   ├── kelola-kategori.php      # CRUD kategori & jenis
│   ├── kelola-komentar.php      # Moderasi komentar
│   └── laporan.php              # Export CSV & cetak PDF
│
├── 📂 assets/
│   ├── 📂 css/style.css         # Stylesheet utama (tema hijau)
│   └── 📂 images/               # Gambar default & kategori
│
├── 📂 config/
│   ├── database.sql             # Skema & data awal database
│   ├── koneksi.php              # Koneksi DB (tidak di-push)
│   └── koneksi.example.php      # Template konfigurasi
│
├── 📂 includes/
│   ├── header.php               # Navbar responsif
│   └── footer.php               # Footer + JavaScript global
│
├── 📂 uploads/
│   ├── resep/                   # Thumbnail resep
│   ├── profil/                  # Foto profil user
│   └── langkah/                 # Foto langkah memasak
│
├── 📂 user/                     # Fitur khusus user login
│   ├── ajax-favorit.php         # Handler AJAX favorit
│   ├── tambah-resep.php         # Form tambah resep
│   ├── edit-resep.php           # Form edit resep
│   ├── hapus-resep.php          # Hapus resep
│   ├── resep-saya.php           # Daftar resep milik user
│   ├── favorit.php              # Daftar resep favorit
│   └── profil.php               # Edit profil & password
│
├── home.php                     # Beranda utama
├── resep.php                    # Daftar semua resep
├── detail-resep.php             # Halaman detail resep
├── kategori.php                 # Semua kategori
├── populer.php                  # Resep terpopuler
├── login.php                    # Halaman login
├── register.php                 # Halaman daftar
└── logout.php                   # Proses logout
```

---

## 🛠️ Tech Stack

<div align="center">

| Layer | Teknologi |
|-------|-----------|
| **Backend** | PHP 8.x Native (tanpa framework) |
| **Database** | MySQL 8.0 |
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| **Icon** | Font Awesome 6.5 |
| **Font** | Playfair Display + Poppins (Google Fonts) |
| **Server** | Apache via XAMPP |

</div>

---

## 📱 Responsif

Tampilan dioptimalkan untuk semua ukuran layar:

```
Desktop  ████████████████████  1200px+
Tablet   █████████████         768px – 1024px
Mobile   ████████              < 768px
```

---

## 📊 Laporan

Fitur laporan admin mendukung:

- 🔍 Filter rentang tanggal & status resep
- 📥 **Export CSV** — dapat dibuka di Excel / Google Sheets
- 🖨️ **Cetak PDF** — via `Ctrl+P` → *Save as PDF*

---

## 📸 Screenshot

> *Screenshot akan ditambahkan setelah semua halaman selesai diuji.*

---

## 👩‍💻 Pembuat

<div align="center">

| | |
|:--:|:--|
| **Nama** | Siti Fatimah Nur Az-Zahra |
| **Project** | Sistem Informasi Resep Masakan |
| **Stack** | PHP Native + MySQL + Vanilla JS |
| **Repository** | [github.com/SitiFatimahNA/Resep-Masakan](https://github.com/SitiFatimahNA/Resep-Masakan) |

<br>

*Dibuat dengan dedikasi untuk tugas akhir Pemrograman Web* 🌿

</div>

---

<div align="center">
<sub>© 2026 Siti Fatimah Nur Az-Zahra — Sistem Informasi Resep Masakan</sub>
</div>
