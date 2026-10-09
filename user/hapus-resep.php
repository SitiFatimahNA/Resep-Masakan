<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /resep-masakan/user/resep-saya.php'); exit; }

// Ambil resep — pastikan milik sendiri atau admin
$stmt = mysqli_prepare($koneksi, "SELECT * FROM resep WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$resep = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$resep || ($resep['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    header('Location: /resep-masakan/user/resep-saya.php');
    exit;
}

// Hapus thumbnail dari folder
if ($resep['thumbnail'] && $resep['thumbnail'] !== 'default-resep.svg') {
    @unlink('../uploads/resep/' . $resep['thumbnail']);
}

// Hapus foto langkah
$lang = mysqli_query($koneksi, "SELECT foto_langkah FROM langkah_resep WHERE id_resep=$id");
while ($l = mysqli_fetch_assoc($lang)) {
    if ($l['foto_langkah']) @unlink('../uploads/langkah/' . $l['foto_langkah']);
}

// Hapus resep (CASCADE akan hapus bahan, langkah, komentar, rating, favorit)
$del = mysqli_prepare($koneksi, "DELETE FROM resep WHERE id = ?");
mysqli_stmt_bind_param($del, 'i', $id);
mysqli_stmt_execute($del);
mysqli_stmt_close($del);

// Redirect sesuai role
if ($_SESSION['role'] === 'admin') {
    header('Location: /resep-masakan/admin/kelola-resep.php?deleted=1');
} else {
    header('Location: /resep-masakan/user/resep-saya.php?deleted=1');
}
exit;
?>
