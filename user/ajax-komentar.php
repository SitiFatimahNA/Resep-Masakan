<?php
ob_start();
session_start();
require_once dirname(__DIR__) . '/config/koneksi.php';
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Belum login']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$uid    = (int)$_SESSION['user_id'];

if ($id <= 0) {
    echo json_encode(['status'=>'error','message'=>'ID tidak valid']);
    exit;
}

$cek = mysqli_prepare($koneksi, "SELECT id, id_user FROM komentar WHERE id = ?");
mysqli_stmt_bind_param($cek, 'i', $id);
mysqli_stmt_execute($cek);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
mysqli_stmt_close($cek);

if (!$row) {
    echo json_encode(['status'=>'error','message'=>'Komentar tidak ditemukan']);
    exit;
}

if ($row['id_user'] != $uid && $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Tidak diizinkan']);
    exit;
}

if ($action === 'hapus') {
    mysqli_query($koneksi, "DELETE FROM komentar WHERE parent_id = $id");
    $del = mysqli_prepare($koneksi, "DELETE FROM komentar WHERE id = ?");
    mysqli_stmt_bind_param($del, 'i', $id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);
    echo json_encode(['status'=>'ok','message'=>'Komentar dihapus']);

} elseif ($action === 'edit') {
    $teks = trim($_POST['teks'] ?? '');
    if (strlen($teks) < 3) {
        echo json_encode(['status'=>'error','message'=>'Komentar terlalu pendek']);
        exit;
    }
    $upd = mysqli_prepare($koneksi, "UPDATE komentar SET komentar = ? WHERE id = ?");
    mysqli_stmt_bind_param($upd, 'si', $teks, $id);
    mysqli_stmt_execute($upd);
    mysqli_stmt_close($upd);
    echo json_encode(['status'=>'ok','message'=>'Komentar diperbarui','teks'=>htmlspecialchars($teks)]);

} else {
    echo json_encode(['status'=>'error','message'=>'Aksi tidak dikenal']);
}
