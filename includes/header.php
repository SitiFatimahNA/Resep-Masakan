<?php
// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Resep Masakan - Temukan dan bagikan resep masakan terbaik">
    <title><?= isset($page_title) ? $page_title . ' | ' : '' ?>Resep Masakan</title>

    <!-- CSS -->
    <link rel="stylesheet" href="/resep-masakan/assets/css/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" href="/resep-masakan/assets/images/favicon.png" type="image/png">
</head>
<body class="page-enter">

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="navbar-brand">
        <span class="logo-icon">🍳</span>
        <a href="/resep-masakan/index.php">
            Resep<span>Masakan</span>
        </a>
    </div>

    <!-- Menu -->
    <ul class="navbar-menu" id="navbar-menu">
        <li>
            <a href="/resep-masakan/index.php" 
               class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
                Beranda
            </a>
        </li>
        <li>
            <a href="/resep-masakan/resep.php" 
               class="<?= $current_page === 'resep.php' ? 'active' : '' ?>">
                Resep
            </a>
        </li>
        <li>
            <a href="/resep-masakan/kategori.php" 
               class="<?= $current_page === 'kategori.php' ? 'active' : '' ?>">
                Kategori
            </a>
        </li>
        <li>
            <a href="/resep-masakan/populer.php" 
               class="<?= $current_page === 'populer.php' ? 'active' : '' ?>">
                Populer
            </a>
        </li>
    </ul>

    <!-- Search + Actions -->
    <div class="navbar-actions">
        <!-- Search -->
        <div class="navbar-search">
            <form action="/resep-masakan/resep.php" method="GET">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" placeholder="Cari resep..." 
                       value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
            </form>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- User sudah login -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/resep-masakan/admin/dashboard.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-tachometer-alt"></i> Admin
                </a>
            <?php else: ?>
                <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-gold btn-sm">
                    <i class="fas fa-plus"></i> Tambah Resep
                </a>
            <?php endif; ?>

            <!-- Avatar Dropdown -->
            <div class="user-dropdown">
                <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($_SESSION['foto'] ?? 'default.png') ?>" 
                     alt="<?= htmlspecialchars($_SESSION['nama']) ?>"
                     class="user-avatar"
                     onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                <div class="dropdown-menu">
                    <a href="/resep-masakan/user/profil.php">
                        <i class="fas fa-user"></i> Profil Saya
                    </a>
                    <a href="/resep-masakan/user/resep-saya.php">
                        <i class="fas fa-utensils"></i> Resep Saya
                    </a>
                    <a href="/resep-masakan/user/favorit.php">
                        <i class="fas fa-heart"></i> Favorit
                    </a>
                    <div class="divider"></div>
                    <a href="/resep-masakan/logout.php" style="color: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Belum login -->
            <a href="/resep-masakan/login.php" class="btn btn-outline btn-sm">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </a>
            <a href="/resep-masakan/register.php" class="btn btn-gold btn-sm">
                <i class="fas fa-user-plus"></i> Daftar
            </a>
        <?php endif; ?>
    </div>

    <!-- Hamburger -->
    <div class="hamburger" id="hamburger" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>
</nav>

<!-- Main Content Wrapper -->
<div class="main-content">
