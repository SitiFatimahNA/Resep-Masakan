<?php
session_start();
require_once '../config/koneksi.php';

header('Content-Type: application/json');

// Harus login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Belum login']);
    exit;
}

$id_resep = (int)($_POST['id_resep'] ?? 0);
$id_user  = (int)$_SESSION['user_id'];

if ($id_resep <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

// Cek apakah sudah difavoritkan
$cek = mysqli_prepare($koneksi, "SELECT id FROM favorit WHERE id_resep = ? AND id_user = ?");
mysqli_stmt_bind_param($cek, 'ii', $id_resep, $id_user);
mysqli_stmt_execute($cek);
mysqli_stmt_store_result($cek);
$sudah = mysqli_stmt_num_rows($cek) > 0;
mysqli_stmt_close($cek);

if ($sudah) {
    // Hapus favorit
    $del = mysqli_prepare($koneksi, "DELETE FROM favorit WHERE id_resep = ? AND id_user = ?");
    mysqli_stmt_bind_param($del, 'ii', $id_resep, $id_user);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);
    echo json_encode(['status' => 'removed']);
} else {
    // Tambah favorit
    $ins = mysqli_prepare($koneksi, "INSERT INTO favorit (id_resep, id_user) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, 'ii', $id_resep, $id_user);
    mysqli_stmt_execute($ins);
    mysqli_stmt_close($ins);
    echo json_encode(['status' => 'added']);
}
?>
