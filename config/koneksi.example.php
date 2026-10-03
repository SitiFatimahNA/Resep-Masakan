<?php
// =====================================================
// Konfigurasi Koneksi Database - CONTOH
// Salin file ini menjadi koneksi.php lalu sesuaikan
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ganti dengan username database kamu
define('DB_PASS', '');            // ganti dengan password database kamu
define('DB_NAME', 'resep_masakan');

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');
?>
