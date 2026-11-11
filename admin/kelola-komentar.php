<?php
$page_title = 'Kelola Komentar';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$aksi = $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

// Toggle status SEBELUM include header
if ($aksi === 'toggle' && $id) {
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM komentar WHERE id=$id"));
    if ($cur) {
        $new = $cur['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        mysqli_query($koneksi, "UPDATE komentar SET status='$new' WHERE id=$id");
    }
    header('Location: /resep-masakan/admin/kelola-komentar.php?msg=updated'); exit;
}

// Hapus SEBELUM include header
if ($aksi === 'hapus' && $id) {
    mysqli_query($koneksi, "DELETE FROM komentar WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-komentar.php?msg=deleted'); exit;
}

$q        = trim($_GET['q'] ?? '');
$filter   = $_GET['filter'] ?? 'semua';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset   = ($page - 1) * $per_page;

$where = ['1=1'];
if ($q) $where[] = "(k.komentar LIKE '%".mysqli_real_escape_string($koneksi,$q)."%' OR u.nama LIKE '%".mysqli_real_escape_string($koneksi,$q)."%')";
if ($filter==='aktif')    $where[] = "k.status='aktif'";
if ($filter==='nonaktif') $where[] = "k.status='nonaktif'";
$w = implode(' AND ', $where);

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM komentar k LEFT JOIN users u ON k.id_user=u.id WHERE $w"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT k.*, u.nama as nama_user, u.foto as foto_user, r.judul as judul_resep, r.slug as slug_resep
     FROM komentar k
     LEFT JOIN users u ON k.id_user = u.id
     LEFT JOIN resep r ON k.id_resep = r.id
     WHERE $w
     ORDER BY k.created_at DESC
     LIMIT $per_page OFFSET $offset");

$total_aktif    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM komentar WHERE status='aktif'"))['t'];
$total_nonaktif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM komentar WHERE status='nonaktif'"))['t'];

// Simpan ke array untuk dual view
$kom_arr = [];
while ($k = mysqli_fetch_assoc($result)) $kom_arr[] = $k;

require_once 'includes/admin-header.php';
?>
<style>
@media (max-width: 768px) {
    .tabel-komentar { display: none !important; }
    .cards-komentar { display: block !important; }
}
@media (min-width: 769px) {
    .tabel-komentar { display: block !important; }
    .cards-komentar { display: none !important; }
}
</style>

<div class="page-header">
    <h1>Kelola <span>Komentar</span></h1>
    <div style="display:flex;gap:6px;flex-shrink:0;">
        <span class="badge badge-aktif" style="padding:5px 10px;font-size:0.72rem;white-space:nowrap;">
            <i class="fas fa-check-circle"></i> <?= $total_aktif ?> Aktif
        </span>
        <span class="badge badge-nonaktif" style="padding:5px 10px;font-size:0.72rem;white-space:nowrap;">
            <i class="fas fa-ban"></i> <?= $total_nonaktif ?> Nonaktif
        </span>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i>
    <?= $_GET['msg']==='deleted'?'Komentar berhasil dihapus.':'Status komentar diperbarui.' ?>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:0.82rem;"></i>
            <input type="text" name="q" class="form-control" placeholder="Cari komentar atau nama user..."
                   value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
        </div>
        <select name="filter" class="form-control" style="width:140px;" onchange="this.form.submit()">
            <option value="semua"    <?= $filter==='semua'?'selected':'' ?>>Semua</option>
            <option value="aktif"    <?= $filter==='aktif'?'selected':'' ?>>Aktif</option>
            <option value="nonaktif" <?= $filter==='nonaktif'?'selected':'' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i> Cari</button>
        <?php if ($q || $filter!=='semua'): ?>
        <a href="/resep-masakan/admin/kelola-komentar.php" class="btn btn-dark btn-sm"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card tabel-komentar">
    <table class="admin-table">
        <thead>
            <tr><th>No</th><th>User</th><th>Komentar</th><th>Resep</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (empty($kom_arr)): ?>
            <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-3);">Tidak ada komentar</td></tr>
        <?php else: ?>
        <?php $no=$offset+1; foreach ($kom_arr as $k): ?>
            <tr>
                <td style="color:var(--text-3);font-size:0.78rem;font-weight:600;"><?= $no++ ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($k['foto_user'] ?? 'default.png') ?>"
                             style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1.5px solid var(--border);"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <span style="font-size:0.82rem;font-weight:600;color:var(--text-1);"><?= htmlspecialchars($k['nama_user']) ?></span>
                    </div>
                </td>
                <td style="font-size:0.82rem;color:var(--text-2);max-width:220px;">
                    <?= htmlspecialchars(mb_strimwidth($k['komentar'],0,70,'...')) ?>
                </td>
                <td>
                    <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($k['slug_resep']) ?>"
                       style="font-size:0.78rem;color:var(--green-3);font-weight:500;text-decoration:none;" target="_blank">
                        <?= htmlspecialchars(mb_strimwidth($k['judul_resep'],0,28,'...')) ?>
                        <i class="fas fa-external-link-alt" style="font-size:0.6rem;"></i>
                    </a>
                </td>
                <td style="font-size:0.75rem;color:var(--text-3);white-space:nowrap;">
                    <?= date('d M Y H:i', strtotime($k['created_at'])) ?>
                </td>
                <td>
                    <a href="?aksi=toggle&id=<?= $k['id'] ?>" class="badge badge-<?= $k['status'] ?>" style="text-decoration:none;cursor:pointer;" title="Klik untuk toggle">
                        <?= ucfirst($k['status']) ?>
                    </a>
                </td>
                <td>
                    <a href="?aksi=hapus&id=<?= $k['id'] ?>" class="btn-action btn-action-delete" title="Hapus"
                       onclick="return confirm('Yakin hapus komentar ini?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ===== MOBILE CARDS ===== -->
<div class="cards-komentar">
<?php if (empty($kom_arr)): ?>
    <div style="background:var(--bg-card);border-radius:16px;padding:3rem;text-align:center;color:var(--text-3);">
        <i class="fas fa-comments" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
        Tidak ada komentar
    </div>
<?php else: ?>
<?php foreach ($kom_arr as $k): ?>
<div style="background:var(--bg-card);border-radius:16px;border:1.5px solid var(--border);padding:1rem;margin-bottom:0.8rem;box-shadow:0 2px 8px rgba(45,106,79,0.06);">

    <!-- Baris 1: Avatar + Nama + Waktu -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.8rem;">
        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($k['foto_user'] ?? 'default.png') ?>"
             style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0;"
             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
        <div style="flex:1;min-width:0;">
            <div style="font-size:0.875rem;font-weight:700;color:var(--text-1);"><?= htmlspecialchars($k['nama_user']) ?></div>
            <div style="font-size:0.68rem;color:var(--text-3);"><?= date('d M Y H:i', strtotime($k['created_at'])) ?></div>
        </div>
        <!-- Status badge -->
        <a href="?aksi=toggle&id=<?= $k['id'] ?>" class="badge badge-<?= $k['status'] ?>" style="text-decoration:none;cursor:pointer;flex-shrink:0;" title="Tap untuk toggle">
            <?= ucfirst($k['status']) ?>
        </a>
    </div>

    <!-- Baris 2: Isi komentar -->
    <div style="font-size:0.85rem;color:var(--text-2);line-height:1.5;padding:0.7rem;background:var(--bg);border-radius:10px;margin-bottom:0.8rem;border-left:3px solid var(--green-4);">
        <?= htmlspecialchars(mb_strimwidth($k['komentar'],0,120,'...')) ?>
    </div>

    <!-- Baris 3: Resep + Tombol Hapus -->
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($k['slug_resep']) ?>"
           style="display:flex;align-items:center;gap:6px;font-size:0.75rem;color:var(--green-3);font-weight:600;text-decoration:none;flex:1;min-width:0;"
           target="_blank">
            <i class="fas fa-book-open" style="flex-shrink:0;"></i>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(mb_strimwidth($k['judul_resep'],0,35,'...')) ?></span>
            <i class="fas fa-external-link-alt" style="font-size:0.6rem;flex-shrink:0;"></i>
        </a>
        <a href="?aksi=hapus&id=<?= $k['id'] ?>"
           style="display:flex;align-items:center;gap:5px;padding:7px 14px;background:rgba(231,76,60,0.08);border-radius:20px;color:#c0392b;text-decoration:none;font-size:0.78rem;font-weight:600;flex-shrink:0;"
           onclick="return confirm('Yakin hapus komentar ini?')">
            <i class="fas fa-trash"></i> Hapus
        </a>
    </div>
</div>
<?php endforeach; endif; ?>
</div>

<?php if ($total_page > 1): ?>
<div class="pagination" style="margin-top:1.5rem;">
    <?php
    $base = '/resep-masakan/admin/kelola-komentar.php?q='.urlencode($q).'&filter='.$filter.'&page=';
    if ($page>1) echo "<a href='{$base}".($page-1)."'><i class='fas fa-chevron-left'></i></a>";
    for ($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++)
        echo "<a href='{$base}{$p}' class='".($p===$page?'active':'')."'>{$p}</a>";
    if ($page<$total_page) echo "<a href='{$base}".($page+1)."'><i class='fas fa-chevron-right'></i></a>";
    ?>
</div>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
