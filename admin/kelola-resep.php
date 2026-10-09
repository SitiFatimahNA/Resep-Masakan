<?php
require_once 'includes/admin-header.php';
require_once '../config/koneksi.php';

$page_title = 'Kelola Resep';

// Handle aksi
$aksi = $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

// Toggle status publik/draft
if ($aksi === 'toggle' && $id) {
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT status FROM resep WHERE id=$id"));
    if ($cur) {
        $new = $cur['status'] === 'publik' ? 'draft' : 'publik';
        mysqli_query($koneksi, "UPDATE resep SET status='$new' WHERE id=$id");
    }
    header('Location: /resep-masakan/admin/kelola-resep.php?msg=updated');
    exit;
}

// Hapus
if ($aksi === 'hapus' && $id) {
    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT thumbnail FROM resep WHERE id=$id"));
    if ($r && $r['thumbnail'] && $r['thumbnail'] !== 'default-resep.svg') {
        @unlink('../uploads/resep/' . $r['thumbnail']);
    }
    $lang = mysqli_query($koneksi, "SELECT foto_langkah FROM langkah_resep WHERE id_resep=$id");
    while ($l = mysqli_fetch_assoc($lang)) {
        if ($l['foto_langkah']) @unlink('../uploads/langkah/' . $l['foto_langkah']);
    }
    mysqli_query($koneksi, "DELETE FROM resep WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-resep.php?msg=deleted');
    exit;
}

// Filter & pencarian
$q         = trim($_GET['q'] ?? '');
$filter    = $_GET['filter'] ?? 'semua';
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 10;
$offset    = ($page - 1) * $per_page;

$where = ['1=1'];
if ($q !== '')         $where[] = "(r.judul LIKE '%".mysqli_real_escape_string($koneksi,$q)."%' OR u.nama LIKE '%".mysqli_real_escape_string($koneksi,$q)."%')";
if ($filter === 'publik') $where[] = "r.status='publik'";
if ($filter === 'draft')  $where[] = "r.status='draft'";
$where_sql = implode(' AND ', $where);

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(DISTINCT r.id) t FROM resep r LEFT JOIN users u ON r.user_id=u.id WHERE $where_sql"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id = u.id
     LEFT JOIN kategori k ON r.id_kategori = k.id
     LEFT JOIN rating rt ON r.id = rt.id_resep
     WHERE $where_sql
     GROUP BY r.id
     ORDER BY r.created_at DESC
     LIMIT $per_page OFFSET $offset");
?>

<div class="page-header">
    <h1>Kelola <span>Resep</span></h1>
    <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-gold">
        <i class="fas fa-plus"></i> Tambah Resep
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?= $_GET['msg']==='deleted' ? 'Resep berhasil dihapus.' : 'Status resep diperbarui.' ?>
</div>
<?php endif; ?>

<!-- Filter & Search -->
<div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.2rem; margin-bottom:1.5rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
    <form method="GET" style="display:flex; gap:8px; flex:1; align-items:center; flex-wrap:wrap;">
        <div style="position:relative; flex:1; min-width:200px;">
            <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem;"></i>
            <input type="text" name="q" class="form-control" placeholder="Cari resep atau nama pembuat..."
                   value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
        </div>
        <select name="filter" class="form-control" style="width:140px;" onchange="this.form.submit()">
            <option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua Status</option>
            <option value="publik" <?= $filter==='publik'?'selected':'' ?>>Publik</option>
            <option value="draft"  <?= $filter==='draft' ?'selected':'' ?>>Draft</option>
        </select>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i> Cari</button>
        <?php if ($q || $filter !== 'semua'): ?>
        <a href="/resep-masakan/admin/kelola-resep.php" class="btn btn-dark btn-sm"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
    <span style="font-size:0.82rem; color:var(--text-muted);"><?= $total ?> resep ditemukan</span>
</div>

<!-- Tabel -->
<div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th>Resep</th>
                <th>Pembuat</th>
                <th>Kategori</th>
                <th>Views</th>
                <th>Rating</th>
                <th>Status</th>
                <th style="width:120px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) === 0): ?>
            <tr><td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">
                <i class="fas fa-search" style="font-size:2rem; display:block; margin-bottom:0.5rem;"></i>
                Tidak ada resep ditemukan
            </td></tr>
        <?php else: ?>
        <?php $no = $offset + 1; while ($r = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td style="color:var(--text-muted); font-size:0.8rem;"><?= $no++ ?></td>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($r['thumbnail']) ?>"
                             style="width:44px; height:44px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0;"
                             onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                        <div>
                            <div style="font-size:0.85rem; font-weight:500; color:var(--text-primary);">
                                <?= htmlspecialchars(mb_strimwidth($r['judul'], 0, 40, '...')) ?>
                            </div>
                            <div style="font-size:0.72rem; color:var(--text-muted);">
                                <?= ucfirst($r['tingkat_kesulitan']) ?> &bull; <?= $r['waktu_masak'] ?> mnt &bull; <?= $r['porsi'] ?> porsi
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.82rem; color:var(--text-secondary);"><?= htmlspecialchars($r['nama_user']) ?></td>
                <td style="font-size:0.82rem; color:var(--text-secondary);"><?= htmlspecialchars($r['nama_kategori']) ?></td>
                <td style="font-size:0.82rem;"><?= number_format($r['views']) ?></td>
                <td>
                    <span style="font-size:0.8rem; color:#f4a124;">
                        <i class="fas fa-star"></i> <?= number_format($r['rata_rating'],1) ?>
                    </span>
                    <span style="font-size:0.72rem; color:var(--text-muted);">(<?= $r['jumlah_rating'] ?>)</span>
                </td>
                <td>
                    <a href="?aksi=toggle&id=<?= $r['id'] ?>"
                       style="display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; text-decoration:none; font-weight:500;
                              background:<?= $r['status']==='publik'?'rgba(45,106,79,0.12)':'rgba(243,156,18,0.12)' ?>;
                              color:<?= $r['status']==='publik'?'var(--green-dark)':'#9a6c00' ?>;">
                        <?= ucfirst($r['status']) ?>
                    </a>
                </td>
                <td>
                    <div style="display:flex; gap:4px;">
                        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>"
                           class="btn btn-dark btn-sm" title="Lihat" target="_blank" style="padding:5px 8px;">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="/resep-masakan/user/edit-resep.php?id=<?= $r['id'] ?>"
                           class="btn btn-outline btn-sm" title="Edit" style="padding:5px 8px;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?aksi=hapus&id=<?= $r['id'] ?>"
                           class="btn btn-danger btn-sm" title="Hapus" style="padding:5px 8px;"
                           onclick="return confirm('Yakin hapus resep ini?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_page > 1): ?>
<div class="pagination" style="margin-top:1.5rem;">
    <?php
    $base = '/resep-masakan/admin/kelola-resep.php?q='.urlencode($q).'&filter='.$filter.'&page=';
    if ($page > 1) echo "<a href='{$base}".($page-1)."'><i class='fas fa-chevron-left'></i></a>";
    for ($p=max(1,$page-2); $p<=min($total_page,$page+2); $p++)
        echo "<a href='{$base}{$p}' class='".($p===$page?'active':'')."'>{$p}</a>";
    if ($page < $total_page) echo "<a href='{$base}".($page+1)."'><i class='fas fa-chevron-right'></i></a>";
    ?>
</div>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
