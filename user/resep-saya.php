<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/login.php');
    exit;
}

$page_title = 'Resep Saya';
$user_id    = (int)$_SESSION['user_id'];

// Pagination
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9;
$offset   = ($page - 1) * $per_page;

// Filter status
$filter = $_GET['filter'] ?? 'semua';
$where  = "user_id = $user_id";
if ($filter === 'publik') $where .= " AND status = 'publik'";
if ($filter === 'draft')  $where .= " AND status = 'draft'";

// Total
$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM resep WHERE $where"))['t'];
$total_page = ceil($total / $per_page);

// Ambil resep
$result = mysqli_query($koneksi,
    "SELECT r.*, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN kategori k ON r.id_kategori = k.id
     LEFT JOIN rating rt ON r.id = rt.id_resep
     WHERE $where
     GROUP BY r.id
     ORDER BY r.created_at DESC
     LIMIT $per_page OFFSET $offset");

$resep_list = [];
while ($row = mysqli_fetch_assoc($result)) $resep_list[] = $row;

// Statistik singkat
$stats = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT
     COUNT(*) as total,
     SUM(status='publik') as publik,
     SUM(status='draft') as draft,
     SUM(views) as total_views
     FROM resep WHERE user_id = $user_id"));

require_once '../includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container">

    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem;" class="animate">
        <div>
            <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--text-primary);">
                Resep <span style="color:var(--green);">Saya</span>
            </h1>
            <div class="divider"></div>
        </div>
        <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-gold">
            <i class="fas fa-plus"></i> Tambah Resep
        </a>
    </div>

    <!-- Alert -->
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Resep berhasil dihapus.</div>
    <?php endif; ?>

    <!-- Statistik -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2rem;" class="animate">
        <?php
        $stat_items = [
            ['label'=>'Total Resep',  'value'=>$stats['total'],       'icon'=>'fa-utensils',  'color'=>'var(--green)'],
            ['label'=>'Publik',       'value'=>$stats['publik'],      'icon'=>'fa-globe',     'color'=>'var(--green)'],
            ['label'=>'Draft',        'value'=>$stats['draft'],       'icon'=>'fa-file-alt',  'color'=>'var(--warning)'],
            ['label'=>'Total Views',  'value'=>number_format($stats['total_views']), 'icon'=>'fa-eye', 'color'=>'var(--info)'],
        ];
        foreach ($stat_items as $s):
        ?>
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.2rem; display:flex; align-items:center; gap:1rem;">
            <div style="width:46px;height:46px;background:var(--bg-secondary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>; font-size:1.1rem;"></i>
            </div>
            <div>
                <div style="font-size:1.4rem; font-weight:700; color:var(--text-primary);"><?= $s['value'] ?></div>
                <div style="font-size:0.78rem; color:var(--text-muted);"><?= $s['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filter Tab -->
    <div style="display:flex; gap:8px; margin-bottom:1.5rem;" class="animate">
        <?php foreach (['semua'=>'Semua', 'publik'=>'Publik', 'draft'=>'Draft'] as $key=>$label): ?>
        <a href="?filter=<?= $key ?>" class="btn <?= $filter===$key ? 'btn-gold' : 'btn-dark' ?> btn-sm">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Grid Resep -->
    <?php if (empty($resep_list)): ?>
    <div style="text-align:center; padding:4rem; background:#fff; border-radius:var(--radius); border:1px solid var(--border);">
        <i class="fas fa-utensils" style="font-size:3rem; color:var(--border); display:block; margin-bottom:1rem;"></i>
        <h3 style="color:var(--text-secondary); margin-bottom:0.5rem;">Belum ada resep</h3>
        <p style="color:var(--text-muted); margin-bottom:1.5rem;">Yuk bagikan resep masakan favoritmu!</p>
        <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-gold">
            <i class="fas fa-plus"></i> Buat Resep Pertama
        </a>
    </div>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($resep_list as $i => $resep): ?>
        <div class="card animate animate-delay-<?= ($i%3)+1 ?>">
            <div style="overflow:hidden; position:relative;">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     alt="<?= htmlspecialchars($resep['judul']) ?>"
                     class="card-img"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <!-- Status badge -->
                <span style="position:absolute; top:12px; left:12px; background:<?= $resep['status']==='publik' ? 'rgba(45,106,79,0.9)' : 'rgba(0,0,0,0.6)' ?>; color:#fff; padding:4px 10px; border-radius:20px; font-size:0.72rem;">
                    <?= ucfirst($resep['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <h3 class="card-title">
                    <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>"
                       style="color:var(--text-primary);">
                        <?= htmlspecialchars($resep['judul']) ?>
                    </a>
                </h3>
                <div class="card-meta">
                    <span><i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt</span>
                    <span><i class="fas fa-eye"></i> <?= number_format($resep['views']) ?></span>
                    <span><i class="fas fa-tag"></i> <?= htmlspecialchars($resep['nama_kategori']) ?></span>
                </div>
            </div>
            <div class="card-footer">
                <span style="font-size:0.75rem; color:var(--text-muted);">
                    <?= date('d M Y', strtotime($resep['created_at'])) ?>
                </span>
                <div style="display:flex; gap:6px;">
                    <a href="/resep-masakan/user/edit-resep.php?id=<?= $resep['id'] ?>"
                       class="btn btn-outline btn-sm" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="/resep-masakan/user/hapus-resep.php?id=<?= $resep['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Yakin ingin menghapus resep ini?')"
                       title="Hapus">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_page > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?filter=<?= $filter ?>&page=<?= $page-1 ?>"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($total_page,$page+2); $p++): ?>
        <a href="?filter=<?= $filter ?>&page=<?= $p ?>" class="<?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_page): ?>
        <a href="?filter=<?= $filter ?>&page=<?= $page+1 ?>"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</div>

<?php require_once '../includes/footer.php'; ?>
