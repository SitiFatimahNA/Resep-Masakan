<?php
require_once 'includes/admin-header.php';
require_once '../config/koneksi.php';

$page_title = 'Dashboard';

// Statistik utama
$total_resep    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep"))['t'];
$total_publik   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep WHERE status='publik'"))['t'];
$total_user     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM users WHERE role='user'"))['t'];
$total_komentar = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM komentar"))['t'];
$total_views    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(views) t FROM resep"))['t'] ?? 0;
$total_rating   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM rating"))['t'];

// Resep terbaru
$resep_terbaru = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori
     FROM resep r
     LEFT JOIN users u ON r.user_id = u.id
     LEFT JOIN kategori k ON r.id_kategori = k.id
     ORDER BY r.created_at DESC LIMIT 5");

// Resep terpopuler
$resep_populer = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user,
     COALESCE(AVG(rt.nilai),0) as rata_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id = u.id
     LEFT JOIN rating rt ON r.id = rt.id_resep
     WHERE r.status = 'publik'
     GROUP BY r.id
     ORDER BY r.views DESC LIMIT 5");

// User terbaru
$user_terbaru = mysqli_query($koneksi,
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Komentar terbaru
$komentar_terbaru = mysqli_query($koneksi,
    "SELECT k.*, u.nama, r.judul
     FROM komentar k
     LEFT JOIN users u ON k.id_user = u.id
     LEFT JOIN resep r ON k.id_resep = r.id
     ORDER BY k.created_at DESC LIMIT 5");

// Data chart resep per bulan (6 bulan terakhir)
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $count = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT COUNT(*) t FROM resep WHERE DATE_FORMAT(created_at,'%Y-%m') = '$bulan'"))['t'];
    $chart_data[] = ['label' => $label, 'count' => (int)$count];
}
?>

<!-- Stat Cards -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1.2rem; margin-bottom:2rem;">
    <?php
    $stats = [
        ['label'=>'Total Resep',    'value'=>$total_resep,           'icon'=>'fa-utensils',      'bg'=>'rgba(64,145,108,0.12)',  'color'=>'var(--green)'],
        ['label'=>'Resep Publik',   'value'=>$total_publik,          'icon'=>'fa-globe',          'bg'=>'rgba(52,152,219,0.12)', 'color'=>'#3498db'],
        ['label'=>'Total Member',   'value'=>$total_user,            'icon'=>'fa-users',          'bg'=>'rgba(155,89,182,0.12)', 'color'=>'#9b59b6'],
        ['label'=>'Total Komentar', 'value'=>$total_komentar,        'icon'=>'fa-comments',       'bg'=>'rgba(243,156,18,0.12)', 'color'=>'#f39c18'],
        ['label'=>'Total Views',    'value'=>number_format($total_views), 'icon'=>'fa-eye',       'bg'=>'rgba(231,76,60,0.12)',  'color'=>'#e74c3c'],
        ['label'=>'Total Rating',   'value'=>$total_rating,          'icon'=>'fa-star',           'bg'=>'rgba(241,196,15,0.12)', 'color'=>'#f1c40f'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="stat-card animate">
        <div class="stat-icon" style="background:<?= $s['bg'] ?>; color:<?= $s['color'] ?>;">
            <i class="fas <?= $s['icon'] ?>"></i>
        </div>
        <div>
            <div class="stat-number"><?= $s['value'] ?></div>
            <div class="stat-label"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart & Populer -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">

    <!-- Chart Resep per Bulan -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem;" class="animate">
        <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-primary); margin-bottom:1.2rem;">
            <i class="fas fa-chart-bar" style="color:var(--green);"></i> Resep per Bulan
        </h3>
        <div style="display:flex; align-items:flex-end; gap:8px; height:160px;">
            <?php
            $max = max(array_column($chart_data, 'count')) ?: 1;
            foreach ($chart_data as $d):
                $h = max(8, ($d['count'] / $max) * 140);
            ?>
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:4px;">
                <span style="font-size:0.7rem; color:var(--text-muted);"><?= $d['count'] ?></span>
                <div style="width:100%; height:<?= $h ?>px; background:linear-gradient(to top, var(--green-dark), var(--green-mid)); border-radius:4px 4px 0 0; transition:height 0.5s;"></div>
                <span style="font-size:0.65rem; color:var(--text-muted); text-align:center; line-height:1.2;"><?= $d['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Resep Terpopuler -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem;" class="animate animate-delay-1">
        <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-primary); margin-bottom:1.2rem;">
            <i class="fas fa-fire" style="color:var(--green);"></i> Resep Terpopuler
        </h3>
        <?php $rank = 1; while ($r = mysqli_fetch_assoc($resep_populer)): ?>
        <div style="display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border);">
            <div style="width:24px; height:24px; background:<?= $rank<=3 ? 'var(--green)' : 'var(--bg-secondary)' ?>; color:<?= $rank<=3 ? '#fff' : 'var(--text-muted)' ?>; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; flex-shrink:0;">
                <?= $rank ?>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-size:0.82rem; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= htmlspecialchars($r['judul']) ?>
                </div>
                <div style="font-size:0.72rem; color:var(--text-muted);">
                    <?= number_format($r['views']) ?> views
                </div>
            </div>
            <div style="font-size:0.75rem; color:#f4a124;">
                <?= number_format($r['rata_rating'],1) ?> <i class="fas fa-star"></i>
            </div>
        </div>
        <?php $rank++; endwhile; ?>
    </div>
</div>

<!-- Tabel Bawah -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">

    <!-- Resep Terbaru -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-primary); margin:0;">
                <i class="fas fa-clock" style="color:var(--green);"></i> Resep Terbaru
            </h3>
            <a href="/resep-masakan/admin/kelola-resep.php" style="font-size:0.78rem; color:var(--green);">Lihat semua</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Oleh</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = mysqli_fetch_assoc($resep_terbaru)): ?>
                <tr>
                    <td>
                        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>"
                           style="color:var(--text-primary); font-size:0.82rem;">
                            <?= htmlspecialchars(mb_strimwidth($r['judul'], 0, 30, '...')) ?>
                        </a>
                    </td>
                    <td style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($r['nama_user']) ?></td>
                    <td>
                        <span class="badge badge-<?= $r['status']==='publik'?'mudah':'sedang' ?>">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Komentar Terbaru -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate animate-delay-1">
        <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-primary); margin:0;">
                <i class="fas fa-comments" style="color:var(--green);"></i> Komentar Terbaru
            </h3>
            <a href="/resep-masakan/admin/kelola-komentar.php" style="font-size:0.78rem; color:var(--green);">Lihat semua</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Dari</th>
                    <th>Komentar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($k = mysqli_fetch_assoc($komentar_terbaru)): ?>
                <tr>
                    <td style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;"><?= htmlspecialchars($k['nama']) ?></td>
                    <td style="font-size:0.78rem; color:var(--text-secondary);">
                        <?= htmlspecialchars(mb_strimwidth($k['komentar'], 0, 40, '...')) ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $k['status']==='aktif'?'mudah':'sulit' ?>">
                            <?= ucfirst($k['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
