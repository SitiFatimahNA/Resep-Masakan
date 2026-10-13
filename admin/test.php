<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Test Path:</h3>";
echo "DIR admin/includes: " . __DIR__ . "<br>";
echo "Path koneksi: " . $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php' . "<br>";
echo "File exists: " . (file_exists($_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php') ? 'YES' : 'NO') . "<br><br>";

echo "<h3>Test Koneksi:</h3>";
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';
if ($koneksi) {
    echo "Koneksi berhasil!<br>";
    $r = mysqli_query($koneksi, "SELECT COUNT(*) as t FROM resep");
    $d = mysqli_fetch_assoc($r);
    echo "Total resep: " . $d['t'] . "<br>";
} else {
    echo "Koneksi GAGAL!<br>";
}
?>
