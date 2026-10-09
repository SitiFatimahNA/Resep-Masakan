<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/login.php');
    exit;
}

$page_title = 'Resep Favorit';
$user_id    = (int)$_SESSION['user_id'];

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9;
$offset   = ($page - 1) * $per_page;

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM favorit WHERE id_user=$user_id"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating,
     f.created_at as tanggal_simpan
     FROM favorit f
     JOIN resep r ON f.id_resep = r.id
     LEFT JOIN users u ON r.user_id = u.id
     LEFT JOIN kategori k ON r.id_kategori = k.id
     LEFT JOIN rating rt ON r.id = rt.id_resep
     WHERE f.id_user = $user_id
     GROUP BY r.id, f.created_at
     ORDER BY f.created_at DESC
     LIMIT $per_page OFFSET $offset");

$favorit_list = [];
while ($row = mysqli_fetch_assoc($result)) $favorit_list[] = $row;

require_once '../includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container">

    <div style="margin-bottom:2rem;" class="animate">
        <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--text-primary);">
            <i class="fas fa-heart" style="color:var(--danger);"></i> Resep <span style="color:var(--green);">Favorit</span>
        </h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">Total <?= $total ?> resep tersimpan</p>
        <div class="divider"></div>
    </div>

    <?php if (empty($favorit_list)): ?>
    <div style="text-align:center; padding:5rem 2rem; background:#fff; border-radius:var(--radius); border:1px solid var(--border);">
        <i class="fas fa-heart" style="font-size:3rem; color:#ddd; display:block; margin-bottom:1rem;"></i>
        <h3 style="color:var(--text-secondary); margin-bottom:0.5rem;">Belum ada resep favorit</h3>
        <p style="color:var(--text-muted); margin-bottom:1.5rem;">Simpan resep yang kamu suka dengan menekan tombol hati</p>
        <a href="/resep-masakan/resep.php" class="btn btn-gold">
            <i class="fas fa-search"></i> Jelajahi Resep
        </a>
    </div>
    <?php else: ?>
    <div class="grid-3">
        <?php foreach ($favorit_list as $i => $resep): ?>
        <div class="card animate animate-delay-<?= ($i%3)+1 ?>">
            <div style="overflow:hidden; position:relative;">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     alt="<?= htmlspecialchars($resep['judul']) ?>"
                     class="card-img"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <span class="badge badge-<?= $resep['tingkat_kesulitan'] ?>"
                      style="position:absolute; top:12px; left:12px;">
                    <?= ucfirst($resep['tingkat_kesulitan']) ?>
                </span>
                <!-- Tombol hapus favorit -->
                <button onclick="hapusFavorit(<?= $resep['id'] ?>, this)"
                        style="position:absolute; top:12px; right:12px; background:rgba(255,255,255,0.9); border:none; width:34px; height:34px; border-radius:50%; cursor:pointer; color:var(--danger); font-size:0.9rem;"
                        title="Hapus dari favorit">
                    <i class="fas fa-heart"></i>
                </button>
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
                    <span><i class="fas fa-users"></i> <?= $resep['porsi'] ?> porsi</span>
                    <span><i class="fas fa-eye"></i> <?= number_format($resep['views']) ?></span>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <div class="stars" style="font-size:0.8rem;">
                        <?php $rat = round($resep['rata_rating']);
                        for ($s=1;$s<=5;$s++) echo $s<=$rat ? '<i class="fas fa-star"></i>' : '<i class="far fa-star empty"></i>'; ?>
                    </div>
                    <span style="font-size:0.75rem; color:var(--text-muted);">(<?= $resep['jumlah_rating'] ?>)</span>
                </div>
            </div>
            <div class="card-footer">
                <span style="font-size:0.78rem; color:var(--text-muted);">
                    <i class="fas fa-user-circle"></i> <?= htmlspecialchars($resep['nama_user']) ?>
                </span>
                <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>"
                   class="btn btn-gold btn-sm">
                    Lihat <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_page > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($p=max(1,$page-2); $p<=min($total_page,$page+2); $p++): ?>
        <a href="?page=<?= $p ?>" class="<?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_page): ?>
        <a href="?page=<?= $page+1 ?>"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</div>

<script>
function hapusFavorit(id, btn) {
    if (!confirm('Hapus dari favorit?')) return;
    fetch('/resep-masakan/user/ajax-favorit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_resep=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'removed') {
            btn.closest('.card').style.opacity = '0';
            btn.closest('.card').style.transform = 'scale(0.9)';
            btn.closest('.card').style.transition = 'all 0.3s ease';
            setTimeout(() => { btn.closest('.card').remove(); }, 300);
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
