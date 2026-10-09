<?php
session_start();
require_once 'config/koneksi.php';
$page_title = 'Resep Populer';

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep WHERE status='publik'"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id=u.id
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE r.status='publik'
     GROUP BY r.id
     ORDER BY r.views DESC
     LIMIT $per_page OFFSET $offset");

$resep_list = [];
while ($row = mysqli_fetch_assoc($result)) $resep_list[] = $row;

require_once 'includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container">

    <div style="margin-bottom:2rem;" class="animate">
        <h1 style="font-family:'Playfair Display',serif; font-size:2rem; color:var(--text-primary);">
            <i class="fas fa-fire" style="color:var(--green);"></i> Resep <span style="color:var(--green);">Terpopuler</span>
        </h1>
        <p style="color:var(--text-muted);">Resep yang paling banyak dilihat dan disukai</p>
        <div class="divider"></div>
    </div>

    <!-- Top 3 podium -->
    <?php if (count($resep_list) >= 3): ?>
    <div style="display:grid; grid-template-columns:1fr 1.2fr 1fr; gap:1rem; margin-bottom:2.5rem; align-items:end;">
        <?php
        $podium = [
            ['idx'=>1, 'h'=>'200px', 'bg'=>'var(--green-light)', 'rank'=>'2'],
            ['idx'=>0, 'h'=>'240px', 'bg'=>'var(--green)',        'rank'=>'1'],
            ['idx'=>2, 'h'=>'180px', 'bg'=>'#95d5b2',             'rank'=>'3'],
        ];
        foreach ($podium as $p):
            $r = $resep_list[$p['idx']];
        ?>
        <div class="animate animate-delay-<?= $p['idx']+1 ?>"
             style="background:#fff; border-radius:var(--radius); overflow:hidden; border:1px solid var(--border); position:relative;">
            <div style="position:absolute; top:12px; left:12px; width:32px; height:32px; background:<?= $p['bg'] ?>; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#fff; font-size:0.9rem; z-index:2;">
                #<?= $p['rank'] ?>
            </div>
            <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($r['thumbnail']) ?>"
                 style="width:100%; height:<?= $p['h'] ?>; object-fit:cover;"
                 onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
            <div style="padding:1rem;">
                <h3 style="font-family:'Playfair Display',serif; font-size:0.95rem; color:var(--text-primary); margin-bottom:6px;">
                    <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>" style="color:inherit;">
                        <?= htmlspecialchars(mb_strimwidth($r['judul'],0,45,'...')) ?>
                    </a>
                </h3>
                <div style="display:flex; gap:12px; font-size:0.78rem; color:var(--text-muted);">
                    <span><i class="fas fa-eye"></i> <?= number_format($r['views']) ?></span>
                    <span style="color:#f4a124;"><i class="fas fa-star"></i> <?= number_format($r['rata_rating'],1) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Grid resep lainnya -->
    <?php if (count($resep_list) > 3): ?>
    <h2 style="font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--text-primary); margin-bottom:1.5rem;">
        Resep <span style="color:var(--green);">Lainnya</span>
    </h2>
    <div class="grid-4">
        <?php foreach (array_slice($resep_list, 3) as $i => $resep): ?>
        <div class="card animate animate-delay-<?= ($i%4)+1 ?>">
            <div style="overflow:hidden; position:relative;">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     alt="<?= htmlspecialchars($resep['judul']) ?>"
                     class="card-img" style="height:160px;"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <div style="position:absolute; top:10px; left:10px; width:28px; height:28px; background:var(--green); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.75rem; color:#fff;">
                    #<?= $i + 4 ?>
                </div>
            </div>
            <div class="card-body" style="padding:0.9rem;">
                <h3 class="card-title" style="font-size:0.9rem;">
                    <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>" style="color:var(--text-primary);">
                        <?= htmlspecialchars($resep['judul']) ?>
                    </a>
                </h3>
                <div class="card-meta" style="font-size:0.75rem;">
                    <span><i class="fas fa-eye"></i> <?= number_format($resep['views']) ?></span>
                    <span><i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt</span>
                </div>
                <div class="stars" style="font-size:0.75rem;">
                    <?php $rat=round($resep['rata_rating']); for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star empty"></i>'; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_page > 1): ?>
    <div class="pagination">
        <?php if ($page>1) echo "<a href='?page=".($page-1)."'><i class='fas fa-chevron-left'></i></a>"; ?>
        <?php for($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++) echo "<a href='?page=$p' class='".($p===$page?'active':'')."'>$p</a>"; ?>
        <?php if ($page<$total_page) echo "<a href='?page=".($page+1)."'><i class='fas fa-chevron-right'></i></a>"; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
