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
    <meta name="description" content="DapurIn - Platform berbagi resep masakan terlengkap di Indonesia">
    <title><?= isset($page_title) ? $page_title . ' | ' : '' ?>DapurIn</title>

    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.png" type="image/png">
</head>
<body class="page-enter">

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="navbar-brand">
        <i class="fas fa-utensils logo-icon" style="font-size:1.4rem;"></i>
        <a href="/index.php">
            Dapur<span>In</span>
        </a>
    </div>

    <!-- Menu -->
    <ul class="navbar-menu" id="navbar-menu">
        <li>
            <a href="/index.php" 
               class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
                Beranda
            </a>
        </li>
        <li>
            <a href="/resep.php" 
               class="<?= $current_page === 'resep.php' ? 'active' : '' ?>">
                Resep
            </a>
        </li>
        <li>
            <a href="/kategori.php" 
               class="<?= $current_page === 'kategori.php' ? 'active' : '' ?>">
                Kategori
            </a>
        </li>
        <li>
            <a href="/populer.php" 
               class="<?= $current_page === 'populer.php' ? 'active' : '' ?>">
                Populer
            </a>
        </li>
    </ul>

    <!-- Search + Actions -->
    <div class="navbar-actions">
        <!-- Search (desktop) -->
        <div class="navbar-search">
            <form action="/resep.php" method="GET">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" placeholder="Cari resep..." 
                       value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
            </form>
        </div>

        <!-- Search mobile � langsung tampil, tidak perlu tap ikon -->
        <form class="navbar-mobile-search-form" action="/resep.php" method="GET">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#8a9e87;font-size:0.82rem;pointer-events:none;z-index:2;"></i>
            <input type="text" name="q" placeholder="Cari resep..."
                   value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                   autocomplete="off"
                   style="padding-left:32px !important;">
        </form>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Tambah Resep / Admin (desktop only) -->
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/admin/dashboard.php" class="btn btn-outline btn-sm navbar-desktop-only">
                    <i class="fas fa-tachometer-alt"></i> Admin
                </a>
            <?php else: ?>
                <a href="/user/tambah-resep.php" class="btn btn-gold btn-sm navbar-desktop-only">
                    <i class="fas fa-plus"></i> Tambah Resep
                </a>
            <?php endif; ?>

            <!-- Avatar Dropdown -->
            <div class="user-dropdown">
                <img src="/uploads/profil/<?= htmlspecialchars($_SESSION['foto'] ?? 'default.png') ?>?v=<?= $_SESSION['user_id'] ?? 0 ?>" 
                     alt="<?= htmlspecialchars($_SESSION['nama']) ?>"
                     class="user-avatar"
                     onerror="this.src='/assets/images/default-avatar.svg'">
                <div class="dropdown-menu">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Admin
                    </a>
                    <div class="divider"></div>
                    <?php endif; ?>
                    <a href="/user/profil.php">
                        <i class="fas fa-user"></i> Profil Saya
                    </a>
                    <a href="/user/resep-saya.php">
                        <i class="fas fa-utensils"></i> Resep Saya
                    </a>
                    <a href="/user/favorit.php">
                        <i class="fas fa-heart"></i> Favorit
                    </a>
                    <div class="divider"></div>
                    <a href="/logout.php" style="color: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Belum login -->
            <a href="/login.php" class="btn btn-outline btn-sm">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </a>
            <a href="/register.php" class="btn btn-gold btn-sm">
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

<!-- Dekorasi background blob -->
<div class="blob-top-right"></div>
<div class="blob-bottom-left"></div>

<!-- Main Content Wrapper -->
<div class="main-content">
