# 🍳 Sistem Informasi Resep Masakan

Aplikasi web berbasis **PHP Native** + **MySQL** untuk berbagi dan mengelola resep masakan. Dibangun sebagai project tugas sekolah dengan fitur lengkap meliputi autentikasi, CRUD resep, pencarian, pagination, laporan, dan panel admin.

---

## 📋 Deskripsi

Sistem Informasi Resep Masakan adalah platform berbagi resep yang memungkinkan pengguna untuk:
- Menemukan ribuan resep dari berbagai kategori
- Membagikan resep masakan sendiri
- Memberikan rating dan komentar pada resep
- Menyimpan resep favorit
- Admin dapat mengelola seluruh konten dan mengekspor laporan

---

## ✨ Fitur Utama

### Fitur Publik
- Halaman beranda dengan hero section modern dan animasi
- Daftar resep dengan filter kategori, jenis, kesulitan, dan pencarian
- Halaman detail resep (bahan, langkah memasak, foto langkah)
- Halaman kategori bahan & jenis masakan
- Halaman resep terpopuler dengan podium top 3

### Fitur User (setelah login)
- Tambah resep dengan form bahan & langkah dinamis
- Edit dan hapus resep milik sendiri
- Rating bintang (1–5) pada resep
- Komentar pada resep
- Simpan & kelola resep favorit
- Edit profil, foto, dan ganti password

### Fitur Admin
- Dashboard dengan statistik & bar chart resep per bulan
- Kelola semua resep (CRUD + toggle status publik/draft)
- Kelola user (toggle role admin/user, hapus)
- Kelola kategori & jenis masakan (CRUD)
- Moderasi komentar (aktif/nonaktif, hapus)
- Laporan resep dengan filter tanggal + **Export CSV** + **Cetak PDF**

---

## 🛡️ Keamanan

- Password di-hash menggunakan `password_hash()` bcrypt
- Semua query menggunakan **prepared statement** mysqli
- Validasi input di sisi server
- Proteksi akses halaman berdasarkan role (admin/user)
- File upload divalidasi tipe dan ukuran

---

## 🗄️ Struktur Database

Database: `resep_masakan` — 9 tabel:

| Tabel | Keterangan |
|-------|-----------|
| `users` | Data pengguna (admin & user) |
| `kategori` | Kategori bahan (Ayam, Ikan, dll) |
| `jenis_masakan` | Jenis sajian (Sarapan, Makan Siang, dll) |
| `resep` | Data resep utama |
| `bahan_resep` | Bahan-bahan tiap resep |
| `langkah_resep` | Langkah memasak tiap resep |
| `komentar` | Komentar user pada resep |
| `rating` | Rating bintang user pada resep |
| `favorit` | Resep favorit user |

### ERD (Relasi Antar Tabel)

```
users ──< resep ──< bahan_resep
            │
            ├──< langkah_resep
            ├──< komentar >── users
            ├──< rating   >── users
            ├──< favorit  >── users
            ├──> kategori
            └──> jenis_masakan
```

---

## 🚀 Cara Instalasi

### Prasyarat
- XAMPP (PHP 8.x + MySQL + Apache)
- Git

### Langkah-langkah

**1. Clone repository**
```bash
cd C:\xampp\htdocs
git clone https://github.com/SitiFatimahNA/Resep-Masakan.git resep-masakan
```

**2. Buat database**
- Buka `http://localhost/phpmyadmin`
- Buat database baru bernama `resep_masakan`
- Import file `config/database.sql`

**3. Konfigurasi koneksi**
```bash
# Salin file contoh
cp config/koneksi.example.php config/koneksi.php
```
Edit `config/koneksi.php` sesuaikan dengan konfigurasi database lokal:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // username database
define('DB_PASS', '');           // password database
define('DB_NAME', 'resep_masakan');
```

**4. Jalankan aplikasi**

Pastikan Apache dan MySQL sudah berjalan di XAMPP, lalu buka:
```
http://localhost/resep-masakan/
```

---

## 👤 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@resepmasakan.com | password |

> Akun user dapat dibuat melalui halaman Register.

---

## 🗂️ Struktur Folder

```
resep-masakan/
├── admin/                  # Halaman panel admin
│   ├── includes/           # Header & footer admin
│   ├── dashboard.php
│   ├── kelola-resep.php
│   ├── kelola-user.php
│   ├── kelola-kategori.php
│   ├── kelola-komentar.php
│   └── laporan.php
├── assets/
│   ├── css/style.css       # Stylesheet utama (tema hijau)
│   ├── js/
│   └── images/             # Gambar default & kategori
├── config/
│   ├── database.sql        # Skema database
│   ├── koneksi.php         # Koneksi DB (tidak di-push)
│   └── koneksi.example.php # Contoh konfigurasi
├── includes/
│   ├── header.php          # Navbar
│   └── footer.php          # Footer + JS global
├── uploads/
│   ├── resep/              # Foto thumbnail resep
│   ├── profil/             # Foto profil user
│   └── langkah/            # Foto langkah memasak
├── user/                   # Halaman khusus user login
│   ├── ajax-favorit.php
│   ├── tambah-resep.php
│   ├── edit-resep.php
│   ├── hapus-resep.php
│   ├── resep-saya.php
│   ├── favorit.php
│   └── profil.php
├── home.php                # Beranda
├── resep.php               # Daftar resep
├── detail-resep.php        # Detail resep
├── kategori.php            # Semua kategori
├── populer.php             # Resep terpopuler
├── login.php
├── register.php
└── logout.php
```

---

## 🛠️ Teknologi yang Digunakan

| Teknologi | Keterangan |
|-----------|-----------|
| PHP 8.x (Native) | Backend tanpa framework |
| MySQL | Database |
| HTML5 + CSS3 | Tampilan |
| JavaScript (Vanilla) | Interaktivitas & animasi |
| Font Awesome 6.5 | Icon |
| Google Fonts | Playfair Display + Poppins |
| XAMPP | Local server development |

---

## 📱 Tampilan Responsif

Aplikasi responsif dan dapat diakses dari berbagai perangkat:
- Desktop (1200px+)
- Tablet (768px – 1024px)
- Mobile (< 768px)

---

## 📊 Fitur Laporan

- Filter berdasarkan rentang tanggal
- Filter berdasarkan status (publik/draft)
- **Export ke CSV** — dapat dibuka di Excel/Google Sheets
- **Cetak / Simpan PDF** — via fitur print browser (Ctrl+P → Save as PDF)

---

## 👩‍💻 Pembuat

| | |
|--|--|
| **Nama** | Siti Fatimah Nur Az-Zahra |
| **Project** | Sistem Informasi Resep Masakan |
| **Teknologi** | PHP Native + MySQL |
| **GitHub** | [SitiFatimahNA/Resep-Masakan](https://github.com/SitiFatimahNA/Resep-Masakan) |

---

## 📸 Screenshot

> Screenshot akan ditambahkan setelah deployment selesai.

---

*Sistem Informasi Resep Masakan — Project Tugas Web*
