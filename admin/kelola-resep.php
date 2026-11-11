<?php
$page_title = 'Kelola Resep';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Handle aksi SEBELUM include header
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
$where     = ['1=1'];

require_once 'includes/admin-header.php';
?>
<style>
@media (max-width: 768px) {
    .tabel-resep { display: none !important; }
    .cards-resep { display: block !important; }
}
@media (min-width: 769px) {
    .tabel-resep { display: block !important; }
    .cards-resep { display: none !important; }
}
</style>
<?php
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

<style>
@media (max-width: 768px) {
    .page-header h1 { font-size: 1.2rem !important; }
    .filter-bar { flex-direction: column !important; gap: 8px !important; }
    .filter-bar form { flex-direction: column !important; gap: 6px !important; }
    .filter-bar form > * { min-width: 100% !important; width: 100% !important; }
}
</style>

<div class="page-header">
    <h1>Kelola <span>Resep</span></h1>
    <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-gold btn-sm">
        <i class="fas fa-plus"></i> Tambah Resep
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?= $_GET['msg']==='deleted' ? 'Resep berhasil dihapus.' : 'Status resep diperbarui.' ?>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;flex:1;align-items:center;flex-wrap:wrap;">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:0.82rem;"></i>
            <input type="text" name="q" class="form-control" placeholder="Cari resep atau pembuat..."
                   value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
        </div>
        <select name="filter" class="form-control" style="width:150px;" onchange="this.form.submit()">
            <option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua Status</option>
            <option value="publik" <?= $filter==='publik'?'selected':'' ?>>Publik</option>
            <option value="draft"  <?= $filter==='draft' ?'selected':'' ?>>Draft</option>
        </select>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i> Cari</button>
        <?php if ($q || $filter !== 'semua'): ?>
        <a href="/resep-masakan/admin/kelola-resep.php" class="btn btn-dark btn-sm"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
    <span style="font-size:0.8rem;color:var(--text-3);background:var(--bg);padding:6px 12px;border-radius:20px;border:1px solid var(--border);"><?= $total ?> resep</span>
</div>

<?php
// Simpan result ke array agar bisa dipakai 2x (desktop + mobile)
$resep_arr = [];
while ($r = mysqli_fetch_assoc($result)) $resep_arr[] = $r;
$no_start = $offset + 1;
?>

<!-- ===== DESKTOP TABLE ===== -->
<div class="admin-card tabel-resep">
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
                <th style="width:110px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($resep_arr)): ?>
            <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text-3);">
                <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
                Tidak ada resep ditemukan
            </td></tr>
        <?php else: ?>
        <?php $no = $no_start; foreach ($resep_arr as $r): ?>
            <tr>
                <td style="color:var(--text-3);font-size:0.78rem;font-weight:600;"><?= $no++ ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($r['thumbnail']) ?>"
                             style="width:46px;height:46px;border-radius:12px;object-fit:cover;flex-shrink:0;border:1.5px solid var(--border);"
                             onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                        <div>
                            <div style="font-size:0.85rem;font-weight:600;color:var(--text-1);"><?= htmlspecialchars(mb_strimwidth($r['judul'],0,38,'...')) ?></div>
                            <div style="font-size:0.7rem;color:var(--text-3);margin-top:2px;">
                                <span class="badge badge-<?= $r['tingkat_kesulitan'] ?>"><?= ucfirst($r['tingkat_kesulitan']) ?></span>
                                &bull; <?= $r['waktu_masak'] ?> mnt &bull; <?= $r['porsi'] ?> porsi
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.82rem;color:var(--text-2);font-weight:500;"><?= htmlspecialchars($r['nama_user']) ?></td>
                <td><span style="font-size:0.78rem;background:rgba(45,106,79,0.08);color:var(--green-2);padding:3px 10px;border-radius:20px;font-weight:500;"><?= htmlspecialchars($r['nama_kategori']) ?></span></td>
                <td style="font-size:0.85rem;font-weight:600;"><?= number_format($r['views']) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <i class="fas fa-star" style="color:#f59e0b;font-size:0.75rem;"></i>
                        <span style="font-size:0.82rem;font-weight:600;"><?= number_format($r['rata_rating'],1) ?></span>
                        <span style="font-size:0.7rem;color:var(--text-3);">(<?= $r['jumlah_rating'] ?>)</span>
                    </div>
                </td>
                <td><a href="?aksi=toggle&id=<?= $r['id'] ?>" class="badge badge-<?= $r['status']==='publik'?'publik':'draft' ?>" style="text-decoration:none;cursor:pointer;"><?= ucfirst($r['status']) ?></a></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>" class="btn-action btn-action-view" target="_blank"><i class="fas fa-eye"></i></a>
                        <a href="/resep-masakan/user/edit-resep.php?id=<?= $r['id'] ?>" class="btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                        <a href="?aksi=hapus&id=<?= $r['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ===== MOBILE CARDS ===== -->
<div class="cards-resep">
<?php if (empty($resep_arr)): ?>
    <div style="background:var(--bg-card);border-radius:16px;padding:3rem;text-align:center;color:var(--text-3);">
        <i class="fas fa-search" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
        Tidak ada resep
    </div>
<?php else: ?>
<?php foreach ($resep_arr as $r): ?>
<div style="background:var(--bg-card);border-radius:16px;border:1.5px solid var(--border);padding:1rem;margin-bottom:0.8rem;box-shadow:0 2px 8px rgba(45,106,79,0.06);">
    <!-- Baris 1: Foto + Info Utama -->
    <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:0.8rem;">
        <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($r['thumbnail']) ?>"
             style="width:60px;height:60px;border-radius:12px;object-fit:cover;flex-shrink:0;border:1.5px solid var(--border);"
             onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
        <div style="flex:1;min-width:0;">
            <div style="font-size:0.9rem;font-weight:700;color:var(--text-1);line-height:1.3;margin-bottom:4px;">
                <?= htmlspecialchars($r['judul']) ?>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                <span class="badge badge-<?= $r['tingkat_kesulitan'] ?>" style="font-size:0.65rem;"><?= ucfirst($r['tingkat_kesulitan']) ?></span>
                <span style="font-size:0.7rem;color:var(--text-3);"><i class="fas fa-clock"></i> <?= $r['waktu_masak'] ?> mnt</span>
                <span style="font-size:0.7rem;color:var(--text-3);"><i class="fas fa-users"></i> <?= $r['porsi'] ?> porsi</span>
            </div>
        </div>
    </div>

    <!-- Baris 2: Meta info dalam 1 baris -->
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:0.6rem 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border);margin-bottom:0.8rem;">
        <!-- Pembuat -->
        <span style="font-size:0.75rem;color:var(--text-2);display:flex;align-items:center;gap:4px;">
            <i class="fas fa-user-circle" style="color:var(--green-3);"></i>
            <?= htmlspecialchars($r['nama_user']) ?>
        </span>
        <!-- Kategori -->
        <span style="font-size:0.72rem;background:rgba(45,106,79,0.08);color:var(--green-2);padding:2px 8px;border-radius:20px;font-weight:500;">
            <?= htmlspecialchars($r['nama_kategori']) ?>
        </span>
        <!-- Views -->
        <span style="font-size:0.75rem;color:var(--text-3);display:flex;align-items:center;gap:3px;">
            <i class="fas fa-eye" style="font-size:0.7rem;"></i> <?= number_format($r['views']) ?>
        </span>
        <!-- Rating -->
        <span style="font-size:0.75rem;color:#b7950b;display:flex;align-items:center;gap:3px;">
            <i class="fas fa-star" style="font-size:0.7rem;"></i>
            <?= number_format($r['rata_rating'],1) ?>
            <span style="color:var(--text-3);">(<?= $r['jumlah_rating'] ?>)</span>
        </span>
        <!-- Status -->
        <a href="?aksi=toggle&id=<?= $r['id'] ?>" class="badge badge-<?= $r['status']==='publik'?'publik':'draft' ?>" style="text-decoration:none;cursor:pointer;font-size:0.7rem;" title="Tap untuk toggle">
            <?= ucfirst($r['status']) ?>
        </a>
    </div>

    <!-- Baris 3: Aksi -->
    <div style="display:flex;gap:8px;">
        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>"
           style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:rgba(52,152,219,0.08);border-radius:10px;color:#2471a3;text-decoration:none;font-size:0.78rem;font-weight:600;"
           target="_blank">
            <i class="fas fa-eye"></i> Lihat
        </a>
        <a href="/resep-masakan/user/edit-resep.php?id=<?= $r['id'] ?>"
           style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:rgba(45,106,79,0.08);border-radius:10px;color:var(--green-2);text-decoration:none;font-size:0.78rem;font-weight:600;">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="?aksi=hapus&id=<?= $r['id'] ?>"
           style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:rgba(231,76,60,0.08);border-radius:10px;color:#c0392b;text-decoration:none;font-size:0.78rem;font-weight:600;"
           onclick="return confirm('Yakin hapus resep ini?')">
            <i class="fas fa-trash"></i> Hapus
        </a>
    </div>
</div>
<?php endforeach; endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_page > 1): ?>
<div class="pagination" style="margin-top:1.5rem;">
    <?php
    $base = '/resep-masakan/admin/kelola-resep.php?q='.urlencode($q).'&filter='.$filter.'&page=';
    if ($page > 1) echo "<a href='{$base}".($page-1)."'><i class='fas fa-chevron-left'></i></a>";
    for ($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++)
        echo "<a href='{$base}{$p}' class='".($p===$page?'active':'')."'>{$p}</a>";
    if ($page < $total_page) echo "<a href='{$base}".($page+1)."'><i class='fas fa-chevron-right'></i></a>";
    ?>
</div>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
