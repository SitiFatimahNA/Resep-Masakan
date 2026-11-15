<?php
// =====================================================
// Konfigurasi Koneksi Database — HOSTING InfinityFree
// =====================================================

$koneksi = mysqli_connect(
    'sql206.infinityfree.com',
    'if0_43428191',
    'yarohaja32',
    'if0_43428191_resep_masakan'
);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');
?>
