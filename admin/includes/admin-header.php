<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Harus login sebagai admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /resep-masakan/login.php');
    exit;
}

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' | ' : '' ?>Admin Panel</title>
    <link rel="stylesheet" href="/resep-masakan/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --sidebar-w: 250px;
            --admin-green: #2d6a4f;
            --admin-green2: #40916c;
        }
        body { background: #f0f5f0; }

        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .admin-sidebar {
            width: var(--sidebar-w);
            background: var(--admin-green);
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand .brand-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: #fff;
            font-weight: 700;
        }

        .sidebar-brand .brand-text span { color: #95d5b2; }

        .sidebar-brand .admin-badge {
            font-size: 0.65rem;
            background: rgba(255,255,255,0.15);
            color: #95d5b2;
            padding: 2px 8px;
            border-radius: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-nav { padding: 1rem 0; flex: 1; }

        .nav-label {
            padding: 0.5rem 1.5rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.4);
            margin-top: 0.5rem;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 1.5rem;
            color: rgba(255,255,255,0.75);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
            padding-left: 1.8rem;
        }

        .sidebar-nav a.active {
            border-left: 3px solid #95d5b2;
        }

        .sidebar-nav a i { width: 18px; text-align: center; }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.6);
            font-size: 0.82rem;
            text-decoration: none;
            margin-bottom: 6px;
            transition: color 0.2s;
        }

        .sidebar-footer a:hover { color: #fff; }

        /* MAIN */
        .admin-main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* TOP BAR */
        .admin-topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .topbar-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-content {
            padding: 2rem;
            flex: 1;
        }

        /* STAT CARDS */
        .stat-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-green);
        }

        .stat-icon {
            width: 52px; height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* TABLE */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .admin-table th {
            background: var(--bg-secondary);
            padding: 10px 14px;
            text-align: left;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }

        .admin-table tr:hover td { background: var(--bg-secondary); }
        .admin-table tr:last-child td { border-bottom: none; }

        /* PAGE HEADER */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .page-header h1 span { color: var(--green); }

        @media (max-width: 768px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-main { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-utensils" style="color:#95d5b2; font-size:1.3rem;"></i>
        <div>
            <div class="brand-text">Resep<span>Masakan</span></div>
            <div class="admin-badge">Admin</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Utama</div>
        <a href="/resep-masakan/admin/dashboard.php" class="<?= $current==='dashboard.php'?'active':'' ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="nav-label">Kelola Konten</div>
        <a href="/resep-masakan/admin/kelola-resep.php" class="<?= $current==='kelola-resep.php'?'active':'' ?>">
            <i class="fas fa-utensils"></i> Kelola Resep
        </a>
        <a href="/resep-masakan/admin/kelola-kategori.php" class="<?= $current==='kelola-kategori.php'?'active':'' ?>">
            <i class="fas fa-tags"></i> Kelola Kategori
        </a>
        <a href="/resep-masakan/admin/kelola-komentar.php" class="<?= $current==='kelola-komentar.php'?'active':'' ?>">
            <i class="fas fa-comments"></i> Kelola Komentar
        </a>

        <div class="nav-label">Pengguna</div>
        <a href="/resep-masakan/admin/kelola-user.php" class="<?= $current==='kelola-user.php'?'active':'' ?>">
            <i class="fas fa-users"></i> Kelola User
        </a>

        <div class="nav-label">Laporan</div>
        <a href="/resep-masakan/admin/laporan.php" class="<?= $current==='laporan.php'?'active':'' ?>">
            <i class="fas fa-file-alt"></i> Laporan
        </a>

        <div class="nav-label">Lainnya</div>
        <a href="/resep-masakan/home.php">
            <i class="fas fa-globe"></i> Lihat Website
        </a>
    </nav>

    <div class="sidebar-footer">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($_SESSION['foto'] ?? 'default.png') ?>"
                 style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid rgba(255,255,255,0.3);"
                 onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
            <div>
                <div style="color:#fff; font-size:0.82rem; font-weight:500;"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                <div style="color:rgba(255,255,255,0.5); font-size:0.72rem;">Administrator</div>
            </div>
        </div>
        <a href="/resep-masakan/user/profil.php"><i class="fas fa-user-cog"></i> Pengaturan</a>
        <a href="/resep-masakan/logout.php" style="color:rgba(255,100,100,0.8)!important;">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>
</aside>

<!-- MAIN AREA -->
<main class="admin-main">
    <!-- TOP BAR -->
    <div class="admin-topbar">
        <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
        <div class="topbar-right">
            <span style="font-size:0.82rem; color:var(--text-muted);">
                <i class="fas fa-calendar"></i> <?= date('d M Y') ?>
            </span>
            <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-gold btn-sm">
                <i class="fas fa-plus"></i> Tambah Resep
            </a>
        </div>
    </div>

    <div class="admin-content">
