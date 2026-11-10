<?php
$page_title = 'Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';
require_once 'includes/admin-header.php';

$total_resep    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep"))['t'];
$total_publik   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep WHERE status='publik'"))['t'];
$total_user     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM users WHERE role='user'"))['t'];
$total_komentar = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM komentar"))['t'];
$total_views    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(views),0) t FROM resep"))['t'];
$total_rating   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM rating"))['t'];

$resep_terbaru = mysqli_query($koneksi,
    "SELECT r.judul, r.slug, r.status, u.nama as nama_user
     FROM resep r LEFT JOIN users u ON r.user_id=u.id
     ORDER BY r.created_at DESC LIMIT 5");

$resep_populer = mysqli_query($koneksi,
    "SELECT r.judul, r.views, COALESCE(AVG(rt.nilai),0) as rata_rating
     FROM resep r LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE r.status='publik' GROUP BY r.id ORDER BY r.views DESC LIMIT 5");

$komentar_terbaru = mysqli_query($koneksi,
    "SELECT k.komentar, k.status, u.nama, r.judul, r.slug
     FROM komentar k LEFT JOIN users u ON k.id_user=u.id LEFT JOIN resep r ON k.id_resep=r.id
     ORDER BY k.created_at DESC LIMIT 5");

$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $esc   = mysqli_real_escape_string($koneksi, $bulan);
    $count = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT COUNT(*) t FROM resep WHERE DATE_FORMAT(created_at,'%Y-%m')='$esc'"))['t'];
    $chart_data[] = ['label'=>$label, 'count'=>(int)$count];
}
?>

<style>
/* Dashboard mobile responsive */
@media (max-width: 768px) {
    .dash-stat-grid  { grid-template-columns: repeat(2,1fr) !important; gap: 0.8rem !important; }
    .dash-mid-grid   { grid-template-columns: 1fr !important; gap: 1rem !important; }
    .dash-bot-grid   { grid-template-columns: 1fr !important; gap: 1rem !important; }
    .dash-stat-num   { font-size: 1.3rem !important; }
    .dash-stat-label { font-size: 0.7rem !important; }
    .dash-stat-sub   { display: none !important; }
    .admin-table th, .admin-table td { font-size: 0.72rem !important; padding: 8px !important; }
    .admin-card-header h3 { font-size: 0.82rem !important; }
    .dash-hide-mobile { display: none !important; }
}
</style>

<!-- ===== STAT CARDS ===== -->
<div class="dash-stat-grid" style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.2rem; margin-bottom:1.5rem;">
<?php
$stats = [
    ['label'=>'Total Resep',    'value'=>$total_resep,                'icon'=>'fa-book-open','bg'=>'rgba(45,106,79,0.1)',   'color'=>'#2d6a4f', 'sub'=>$total_publik.' publik'],
    ['label'=>'Total Member',   'value'=>$total_user,                 'icon'=>'fa-users',    'bg'=>'rgba(52,152,219,0.1)',  'color'=>'#2471a3', 'sub'=>'terdaftar'],
    ['label'=>'Komentar',       'value'=>$total_komentar,             'icon'=>'fa-comments', 'bg'=>'rgba(243,156,18,0.1)', 'color'=>'#b7770d', 'sub'=>'semua'],
    ['label'=>'Total Views',    'value'=>number_format($total_views), 'icon'=>'fa-eye',      'bg'=>'rgba(155,89,182,0.1)', 'color'=>'#7d3c98', 'sub'=>'tayang'],
    ['label'=>'Rating',         'value'=>$total_rating,               'icon'=>'fa-star',     'bg'=>'rgba(241,196,15,0.1)', 'color'=>'#b7950b', 'sub'=>'ulasan'],
    ['label'=>'Publik',         'value'=>$total_publik,               'icon'=>'fa-globe',    'bg'=>'rgba(46,204,113,0.1)', 'color'=>'#1e8449', 'sub'=>'dari '.$total_resep],
];
foreach ($stats as $s):
?>
<div class="stat-card" style="padding:1rem;">
    <div class="stat-icon" style="background:<?= $s['bg'] ?>; color:<?= $s['color'] ?>; width:40px; height:40px; border-radius:12px; flex-shrink:0;">
        <i class="fas <?= $s['icon'] ?>" style="font-size:0.95rem;"></i>
    </div>
    <div style="min-width:0;">
        <div class="dash-stat-num" style="font-size:1.5rem; font-weight:700; color:var(--text-1); line-height:1; letter-spacing:-0.5px;"><?= $s['value'] ?></div>
        <div class="dash-stat-label" style="font-size:0.72rem; color:var(--text-3); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= $s['label'] ?></div>
        <div class="dash-stat-sub" style="font-size:0.65rem; color:var(--text-3); margin-top:1px;"><?= $s['sub'] ?></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ===== CHART + POPULER ===== -->
<div class="dash-mid-grid" style="display:grid; grid-template-columns:1.2fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">

    <!-- Chart -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-chart-bar"></i> Resep per Bulan</h3>
            <span style="font-size:0.68rem; color:var(--text-3);">6 bulan</span>
        </div>
        <div style="padding:1rem;">
            <div style="display:flex; align-items:flex-end; gap:6px; height:120px;">
            <?php
            $max = max(array_column($chart_data,'count')) ?: 1;
            foreach ($chart_data as $d):
                $h = max(6, ($d['count']/$max)*100);
                $isMax = $d['count'] === max(array_column($chart_data,'count'));
            ?>
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px;">
                <span style="font-size:0.6rem; color:var(--text-3); font-weight:600;"><?= $d['count'] ?></span>
                <div style="width:100%; height:<?= $h ?>px; background:<?= $isMax?'linear-gradient(to top,var(--green-1),var(--green-4))':'linear-gradient(to top,var(--green-3),var(--green-5))' ?>; border-radius:4px 4px 0 0; opacity:<?= $isMax?'1':'0.6' ?>;"></div>
                <span style="font-size:0.58rem; color:var(--text-3);"><?= $d['label'] ?></span>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Populer -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-fire"></i> Terpopuler</h3>
        </div>
        <div style="padding:0.2rem 0;">
        <?php $rank=1; while($r=mysqli_fetch_assoc($resep_populer)): ?>
        <div style="display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid var(--border);">
            <div style="width:24px; height:24px; background:<?= $rank<=3?'linear-gradient(135deg,var(--green-2),var(--green-3))':'var(--bg)' ?>; color:<?= $rank<=3?'#fff':'var(--text-3)' ?>; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.65rem; font-weight:700; flex-shrink:0;"><?= $rank ?></div>
            <div style="flex:1; min-width:0;">
                <div style="font-size:0.75rem; font-weight:600; color:var(--text-1); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($r['judul']) ?></div>
                <div style="font-size:0.65rem; color:var(--text-3);"><?= number_format($r['views']) ?> views · <?= number_format($r['rata_rating'],1) ?>⭐</div>
            </div>
        </div>
        <?php $rank++; endwhile; ?>
        </div>
    </div>
</div>

<!-- ===== TABEL BAWAH ===== -->
<div class="dash-bot-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">

    <!-- Resep Terbaru -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-clock"></i> Resep Terbaru</h3>
            <a href="/resep-masakan/admin/kelola-resep.php" style="font-size:0.72rem; color:var(--green-3); font-weight:600; text-decoration:none; white-space:nowrap;">Lihat →</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th class="dash-hide-mobile">Oleh</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($resep_terbaru)===0): ?>
                <tr><td colspan="3" style="text-align:center; color:var(--text-3); padding:2rem; font-size:0.82rem;">Belum ada resep</td></tr>
            <?php else: ?>
            <?php while($r=mysqli_fetch_assoc($resep_terbaru)): ?>
                <tr>
                    <td style="max-width:120px;">
                        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>"
                           style="color:var(--text-1); font-size:0.78rem; font-weight:500; text-decoration:none; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <?= htmlspecialchars($r['judul']) ?>
                        </a>
                    </td>
                    <td class="dash-hide-mobile" style="font-size:0.72rem; color:var(--text-3); white-space:nowrap;"><?= htmlspecialchars(mb_strimwidth($r['nama_user'],0,12,'...')) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>" style="font-size:0.65rem; padding:2px 7px;"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Komentar Terbaru -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-comments"></i> Komentar Terbaru</h3>
            <a href="/resep-masakan/admin/kelola-komentar.php" style="font-size:0.72rem; color:var(--green-3); font-weight:600; text-decoration:none; white-space:nowrap;">Lihat →</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Komentar</th>
                    <th class="dash-hide-mobile">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($komentar_terbaru)===0): ?>
                <tr><td colspan="3" style="text-align:center; color:var(--text-3); padding:2rem; font-size:0.82rem;">Belum ada komentar</td></tr>
            <?php else: ?>
            <?php while($k=mysqli_fetch_assoc($komentar_terbaru)): ?>
                <tr>
                    <td style="font-size:0.75rem; font-weight:600; color:var(--text-1); white-space:nowrap; max-width:60px; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(mb_strimwidth($k['nama'],0,8,'...')) ?></td>
                    <td style="font-size:0.72rem; color:var(--text-3); max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars(mb_strimwidth($k['komentar'],0,25,'...')) ?></td>
                    <td class="dash-hide-mobile"><span class="badge badge-<?= $k['status'] ?>" style="font-size:0.65rem; padding:2px 7px;"><?= ucfirst($k['status']) ?></span></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
