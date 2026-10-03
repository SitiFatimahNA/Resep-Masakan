<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beranda | Resep Masakan</title>
</head>
<body style="background:#0f0f0f; color:#f0f0f0; font-family:sans-serif; text-align:center; padding:5rem;">
    <h1 style="color:#c9a84c;">🍳 Resep Masakan</h1>
    <p>Halaman beranda sedang dalam pembangunan.</p>
    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Halo, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></strong>!</p>
        <a href="/resep-masakan/logout.php" style="color:#c9a84c;">Keluar</a>
    <?php else: ?>
        <a href="/resep-masakan/login.php" style="color:#c9a84c;">Masuk</a> |
        <a href="/resep-masakan/register.php" style="color:#c9a84c;">Daftar</a>
    <?php endif; ?>
</body>
</html>
