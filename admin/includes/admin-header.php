<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
    <title><?= isset($page_title) ? $page_title . ' — ' : '' ?>DapurIn Admin</title>
    <link rel="stylesheet" href="/resep-masakan/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* =====================================================
       ADMIN PANEL — Modern Fresh Theme
       ===================================================== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --sidebar-w: 260px;
        --topbar-h: 68px;
        --green-1: #1a4731;
        --green-2: #2d6a4f;
        --green-3: #40916c;
        --green-4: #52b788;
        --green-5: #95d5b2;
        --green-pale: #d8f3dc;
        --bg: #f4f7f4;
        --bg-card: #ffffff;
        --border: #e4ede6;
        --text-1: #1a2e1a;
        --text-2: #4a6741;
        --text-3: #8a9e87;
        --radius: 16px;
        --radius-sm: 10px;
        --shadow-sm: 0 1px 3px rgba(45,106,79,0.08), 0 1px 2px rgba(45,106,79,0.05);
        --shadow: 0 4px 16px rgba(45,106,79,0.1);
        --shadow-lg: 0 8px 32px rgba(45,106,79,0.15);
        --transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
    }

    body {
        font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif;
        background: var(--bg);
        color: var(--text-1);
        min-height: 100vh;
    }

    /* ===== LAYOUT ===== */
    .admin-wrapper { display: flex; min-height: 100vh; }

    /* ===== SIDEBAR ===== */
    .admin-sidebar {
        width: var(--sidebar-w);
        background: var(--green-1);
        position: fixed;
        top: 0; left: 0;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 200;
        display: flex;
        flex-direction: column;
        transition: var(--transition);
    }

    .admin-sidebar::-webkit-scrollbar { width: 4px; }
    .admin-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

    /* Brand */
    .sidebar-brand {
        padding: 1.5rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        flex-shrink: 0;
    }

    .sidebar-brand-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, var(--green-3), var(--green-4));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        box-shadow: 0 4px 12px rgba(64,145,108,0.4);
        flex-shrink: 0;
    }

    .sidebar-brand-text { line-height: 1.2; }
    .sidebar-brand-text .name {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.3px;
    }
    .sidebar-brand-text .name span { color: var(--green-4); }
    .sidebar-brand-text .badge {
        font-size: 0.62rem;
        color: rgba(255,255,255,0.45);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-weight: 500;
    }

    /* Nav */
    .sidebar-nav { padding: 1rem 0.8rem; flex: 1; }

    .nav-section {
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        color: rgba(255,255,255,0.3);
        padding: 1rem 0.8rem 0.4rem;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: var(--radius-sm);
        color: rgba(255,255,255,0.65);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: var(--transition);
        margin-bottom: 2px;
        position: relative;
    }

    .nav-item:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
        transform: translateX(3px);
    }

    .nav-item.active {
        background: linear-gradient(135deg, rgba(82,183,136,0.25), rgba(64,145,108,0.15));
        color: #fff;
        border: 1px solid rgba(82,183,136,0.2);
    }

    .nav-item.active .nav-icon {
        background: linear-gradient(135deg, var(--green-3), var(--green-4));
        color: #fff;
        box-shadow: 0 3px 10px rgba(64,145,108,0.4);
    }

    .nav-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        background: rgba(255,255,255,0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        flex-shrink: 0;
        transition: var(--transition);
    }

    /* Sidebar Footer */
    .sidebar-footer {
        padding: 1rem 0.8rem;
        border-top: 1px solid rgba(255,255,255,0.08);
        flex-shrink: 0;
    }

    .sidebar-user {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: var(--radius-sm);
        background: rgba(255,255,255,0.06);
        margin-bottom: 6px;
    }

    .sidebar-user img {
        width: 34px; height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(82,183,136,0.4);
        flex-shrink: 0;
    }

    .sidebar-user .user-info { min-width: 0; flex: 1; }
    .sidebar-user .user-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sidebar-user .user-role {
        font-size: 0.68rem;
        color: rgba(255,255,255,0.4);
    }

    .sidebar-footer-links { display: flex; gap: 4px; }
    .sidebar-footer-links a {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 8px;
        border-radius: var(--radius-sm);
        font-size: 0.78rem;
        color: rgba(255,255,255,0.55);
        text-decoration: none;
        background: rgba(255,255,255,0.05);
        transition: var(--transition);
    }
    .sidebar-footer-links a:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .sidebar-footer-links a.logout:hover { background: rgba(231,76,60,0.2); color: #ff6b6b; }

    /* ===== MAIN ===== */
    .admin-main {
        margin-left: var(--sidebar-w);
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* ===== TOPBAR ===== */
    .admin-topbar {
        height: var(--topbar-h);
        background: var(--bg-card);
        border-bottom: 1px solid var(--border);
        padding: 0 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: var(--shadow-sm);
    }

    .topbar-left { display: flex; align-items: center; gap: 12px; }

    .topbar-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-1);
        letter-spacing: -0.3px;
    }

    .topbar-breadcrumb {
        font-size: 0.78rem;
        color: var(--text-3);
    }

    .topbar-right { display: flex; align-items: center; gap: 10px; }

    .topbar-date {
        font-size: 0.8rem;
        color: var(--text-3);
        background: var(--bg);
        padding: 6px 12px;
        border-radius: 20px;
        border: 1px solid var(--border);
    }

    .topbar-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 18px;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        background: linear-gradient(135deg, var(--green-2), var(--green-3));
        color: #fff;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 3px 10px rgba(45,106,79,0.3);
    }

    .topbar-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 16px rgba(45,106,79,0.4);
        color: #fff;
    }

    /* ===== CONTENT ===== */
    .admin-content { padding: 2rem; flex: 1; }

    /* ===== PAGE HEADER ===== */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.8rem;
    }

    .page-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-1);
        letter-spacing: -0.5px;
    }

    .page-header h1 span { color: var(--green-3); }

    /* ===== STAT CARDS ===== */
    .stat-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        padding: 1.4rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: 1px solid var(--border);
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow);
        border-color: var(--green-5);
    }

    .stat-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-1);
        line-height: 1;
        margin-bottom: 3px;
        letter-spacing: -0.5px;
    }

    .stat-label { font-size: 0.78rem; color: var(--text-3); font-weight: 500; }

    /* ===== TABLE ===== */
    .admin-card {
        background: var(--bg-card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .admin-card-header {
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(to right, rgba(216,243,220,0.3), transparent);
    }

    .admin-card-header h3 {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-1);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .admin-card-header h3 i { color: var(--green-3); }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .admin-table th {
        background: var(--bg);
        padding: 10px 16px;
        text-align: left;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--text-3);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        border-bottom: 1px solid var(--border);
    }

    .admin-table td {
        padding: 13px 16px;
        border-bottom: 1px solid var(--border);
        color: var(--text-1);
        vertical-align: middle;
    }

    .admin-table tr:last-child td { border-bottom: none; }

    .admin-table tr:hover td {
        background: rgba(216,243,220,0.2);
    }

    /* ===== FILTER BAR ===== */
    .filter-bar {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.2rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
        box-shadow: var(--shadow-sm);
    }

    /* ===== BADGES ===== */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .badge-publik  { background: rgba(45,106,79,0.1);  color: var(--green-2); }
    .badge-draft   { background: rgba(233,196,106,0.15); color: #9a6c00; }
    .badge-aktif   { background: rgba(45,106,79,0.1);  color: var(--green-2); }
    .badge-nonaktif { background: rgba(231,76,60,0.1); color: #c0392b; }
    .badge-mudah   { background: rgba(45,106,79,0.1);  color: var(--green-2); }
    .badge-sedang  { background: rgba(233,196,106,0.15); color: #9a6c00; }
    .badge-sulit   { background: rgba(231,76,60,0.1);  color: #c0392b; }
    .badge-admin   { background: rgba(231,76,60,0.1);  color: #c0392b; }
    .badge-user    { background: rgba(45,106,79,0.1);  color: var(--green-2); }

    /* ===== ACTION BUTTONS ===== */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px; height: 32px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-size: 0.8rem;
        text-decoration: none;
        transition: var(--transition);
    }

    .btn-action-view { background: rgba(52,152,219,0.1); color: #2980b9; }
    .btn-action-view:hover { background: #3498db; color: #fff; }

    .btn-action-edit { background: rgba(45,106,79,0.1); color: var(--green-2); }
    .btn-action-edit:hover { background: var(--green-3); color: #fff; }

    .btn-action-delete { background: rgba(231,76,60,0.1); color: #c0392b; }
    .btn-action-delete:hover { background: #e74c3c; color: #fff; }

    .btn-action-toggle { background: rgba(243,156,18,0.1); color: #d68910; }
    .btn-action-toggle:hover { background: #f39c12; color: #fff; }

    /* ===== FORM CONTROLS ===== */
    .form-control {
        width: 100%;
        background: var(--bg);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 9px 13px;
        color: var(--text-1);
        font-size: 0.875rem;
        font-family: inherit;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--green-3);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(64,145,108,0.1);
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-2);
        margin-bottom: 5px;
    }

    .form-group { margin-bottom: 1.1rem; }

    /* ===== ALERT ===== */
    .alert {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 0.875rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .alert-success {
        background: rgba(45,106,79,0.08);
        border: 1px solid rgba(45,106,79,0.2);
        color: var(--green-2);
    }

    .alert-danger {
        background: rgba(231,76,60,0.08);
        border: 1px solid rgba(231,76,60,0.2);
        color: #c0392b;
    }

    /* ===== PAGINATION ===== */
    .pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 1.5rem;
    }

    .pagination a, .pagination span {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px; height: 36px;
        border-radius: 10px;
        font-size: 0.85rem;
        background: var(--bg-card);
        border: 1px solid var(--border);
        color: var(--text-2);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
    }

    .pagination a:hover { border-color: var(--green-3); color: var(--green-3); }

    .pagination .active {
        background: var(--green-3);
        border-color: var(--green-3);
        color: #fff;
        font-weight: 700;
    }

    /* ===== MISC ===== */
    .divider { height: 1px; background: var(--border); margin: 1rem 0; }

    .text-muted { color: var(--text-3) !important; }
    .text-green { color: var(--green-3) !important; }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        font-family: inherit;
    }

    .btn-gold {
        background: linear-gradient(135deg, var(--green-2), var(--green-3));
        color: #fff;
        box-shadow: 0 3px 10px rgba(45,106,79,0.3);
    }

    .btn-gold:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 16px rgba(45,106,79,0.4);
        color: #fff;
    }

    .btn-outline {
        background: transparent;
        color: var(--green-2);
        border: 1.5px solid var(--green-3);
    }

    .btn-outline:hover {
        background: var(--green-3);
        color: #fff;
    }

    .btn-dark {
        background: var(--bg);
        color: var(--text-2);
        border: 1.5px solid var(--border);
    }

    .btn-dark:hover {
        background: var(--border);
        color: var(--text-1);
    }

    .btn-danger { background: #e74c3c; color: #fff; }
    .btn-danger:hover { background: #c0392b; color: #fff; transform: translateY(-1px); }

    .btn-sm { padding: 6px 12px; font-size: 0.78rem; }
    .btn-lg { padding: 11px 24px; font-size: 0.92rem; }

    .w-100 { width: 100%; }

    /* Divider section */
    .section-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 1.5rem 0 1rem;
    }

    .section-divider span {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--text-3);
        white-space: nowrap;
    }

    .section-divider::before,
    .section-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    @media (max-width: 768px) {
        .admin-sidebar { transform: translateX(-100%); }
        .admin-main { margin-left: 0; }
        .admin-content { padding: 1rem; }
    }
    </style>
</head>
<body>
<div class="admin-wrapper">

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">
            <i class="fas fa-utensils"></i>
        </div>
        <div class="sidebar-brand-text">
            <div class="name">Dapur<span>In</span></div>
            <div class="badge">Admin Panel</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Utama</div>
        <a href="/resep-masakan/admin/dashboard.php" class="nav-item <?= $current==='dashboard.php'?'active':'' ?>">
            <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
            Dashboard
        </a>

        <div class="nav-section">Konten</div>
        <a href="/resep-masakan/admin/kelola-resep.php" class="nav-item <?= $current==='kelola-resep.php'?'active':'' ?>">
            <div class="nav-icon"><i class="fas fa-book-open"></i></div>
            Kelola Resep
        </a>
        <a href="/resep-masakan/admin/kelola-kategori.php" class="nav-item <?= $current==='kelola-kategori.php'?'active':'' ?>">
            <div class="nav-icon"><i class="fas fa-tags"></i></div>
            Kategori & Jenis
        </a>
        <a href="/resep-masakan/admin/kelola-komentar.php" class="nav-item <?= $current==='kelola-komentar.php'?'active':'' ?>">
            <div class="nav-icon"><i class="fas fa-comments"></i></div>
            Moderasi Komentar
        </a>

        <div class="nav-section">Pengguna</div>
        <a href="/resep-masakan/admin/kelola-user.php" class="nav-item <?= $current==='kelola-user.php'?'active':'' ?>">
            <div class="nav-icon"><i class="fas fa-users"></i></div>
            Kelola User
        </a>

        <div class="nav-section">Laporan</div>
        <a href="/resep-masakan/admin/laporan.php" class="nav-item <?= $current==='laporan.php'?'active':'' ?>">
            <div class="nav-icon"><i class="fas fa-file-chart-line"></i></div>
            Laporan & Export
        </a>

        <div class="nav-section">Lainnya</div>
        <a href="/resep-masakan/home.php" class="nav-item" target="_blank">
            <div class="nav-icon"><i class="fas fa-globe"></i></div>
            Lihat Website
            <i class="fas fa-external-link-alt" style="font-size:0.6rem; margin-left:auto; opacity:0.5;"></i>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($_SESSION['foto'] ?? 'default.png') ?>"
                 alt="<?= htmlspecialchars($_SESSION['nama']) ?>"
                 onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <div class="sidebar-footer-links">
            <a href="/resep-masakan/user/profil.php" title="Pengaturan">
                <i class="fas fa-cog"></i> Pengaturan
            </a>
            <a href="/resep-masakan/logout.php" class="logout" title="Keluar">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </div>
    </div>
</aside>

<!-- MAIN -->
<main class="admin-main">
    <div class="admin-topbar">
        <div class="topbar-left">
            <div>
                <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
                <div class="topbar-breadcrumb">DapurIn / <?= $page_title ?? 'Dashboard' ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?>
            </div>
            <a href="/resep-masakan/user/tambah-resep.php" class="topbar-btn">
                <i class="fas fa-plus"></i> Tambah Resep
            </a>
        </div>
    </div>

    <div class="admin-content">
