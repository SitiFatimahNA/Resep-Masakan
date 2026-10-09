<?php
require_once 'includes/admin-header.php';
require_once '../config/koneksi.php';

$page_title = 'Kelola Komentar';

$aksi = $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

// Toggle status aktif/nonaktif
if ($aksi === 'toggle' && $id) {
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM komentar WHERE id=$id"));
    if ($cur) {
        $new = $cur['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        mysqli_query($koneksi, "UPDATE komentar SET status='$new' WHERE id=$id");
    }
    header('Location: /resep-masakan/admin/kelola-komentar.php?msg=updated'); exit;
}

// Hapus
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
?>

<div class="page-header">
    <h1>Kelola <span>Komentar</span></h1>
    <div style="display:flex; gap:8px;">
        <span style="background:rgba(45,106,79,0.1); color:var(--green-dark); padding:6px 14px; border-radius:20px; font-size:0.82rem;">
            <i class="fas fa-check-circle"></i> <?= $total_aktif ?> Aktif
        </span>
        <span style="background:rgba(231,76,60,0.1); color:var(--danger); padding:6px 14px; border-radius:20px; font-size:0.82rem;">
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
<div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.2rem; margin-bottom:1.5rem; display:flex; gap:1rem; flex-wrap:wrap;">
    <form method="GET" style="display:flex; gap:8px; flex:1; flex-wrap:wrap;">
        <div style="position:relative; flex:1; min-width:200px;">
            <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem;"></i>
            <input type="text" name="q" class="form-control" placeholder="Cari komentar atau nama user..."
                   value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
        </div>
        <select name="filter" class="form-control" style="width:140px;" onchange="this.form.submit()">
            <option value="semua"    <?= $filter==='semua'?'selected':'' ?>>Semua</option>
            <option value="aktif"    <?= $filter==='aktif'?'selected':'' ?>>Aktif</option>
            <option value="nonaktif" <?= $filter==='nonaktif'?'selected':'' ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i></button>
        <?php if ($q || $filter!=='semua'): ?>
        <a href="/resep-masakan/admin/kelola-komentar.php" class="btn btn-dark btn-sm"><i class="fas fa-times"></i></a>
        <?php endif; ?>
    </form>
</div>

<div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate">
    <table class="admin-table">
        <thead>
            <tr><th>No</th><th>User</th><th>Komentar</th><th>Resep</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result)===0): ?>
            <tr><td colspan="7" style="text-align:center; padding:3rem; color:var(--text-muted);">Tidak ada komentar</td></tr>
        <?php else: ?>
        <?php $no=$offset+1; while ($k = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td style="color:var(--text-muted); font-size:0.8rem;"><?= $no++ ?></td>
                <td>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($k['foto_user'] ?? 'default.png') ?>"
                             style="width:32px; height:32px; border-radius:50%; object-fit:cover;"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <span style="font-size:0.82rem;"><?= htmlspecialchars($k['nama_user']) ?></span>
                    </div>
                </td>
                <td style="font-size:0.82rem; color:var(--text-secondary); max-width:250px;">
                    <?= htmlspecialchars(mb_strimwidth($k['komentar'], 0, 80, '...')) ?>
                </td>
                <td>
                    <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($k['slug_resep']) ?>"
                       style="font-size:0.78rem; color:var(--green);" target="_blank">
                        <?= htmlspecialchars(mb_strimwidth($k['judul_resep'], 0, 30, '...')) ?>
                    </a>
                </td>
                <td style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap;">
                    <?= date('d M Y H:i', strtotime($k['created_at'])) ?>
                </td>
                <td>
                    <a href="?aksi=toggle&id=<?= $k['id'] ?>"
                       style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; text-decoration:none; font-weight:500;
                              background:<?= $k['status']==='aktif'?'rgba(45,106,79,0.12)':'rgba(231,76,60,0.12)' ?>;
                              color:<?= $k['status']==='aktif'?'var(--green-dark)':'var(--danger)' ?>;">
                        <?= ucfirst($k['status']) ?>
                    </a>
                </td>
                <td>
                    <a href="?aksi=hapus&id=<?= $k['id'] ?>" class="btn btn-danger btn-sm" style="padding:5px 8px;"
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
