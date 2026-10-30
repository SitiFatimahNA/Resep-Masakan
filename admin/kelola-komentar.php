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

require_once 'includes/admin-header.php';
?>

<div class="page-header">
    <h1>Kelola <span>Komentar</span></h1>
    <div style="display:flex;gap:8px;">
        <span class="badge badge-aktif" style="padding:6px 14px;font-size:0.78rem;">
            <i class="fas fa-check-circle"></i> <?= $total_aktif ?> Aktif
        </span>
        <span class="badge badge-nonaktif" style="padding:6px 14px;font-size:0.78rem;">
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

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr><th>No</th><th>User</th><th>Komentar</th><th>Resep</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result)===0): ?>
            <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-3);">Tidak ada komentar</td></tr>
        <?php else: ?>
        <?php $no=$offset+1; while ($k = mysqli_fetch_assoc($result)): ?>
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
        <?php endwhile; endif; ?>
        </tbody>
    </table>
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
