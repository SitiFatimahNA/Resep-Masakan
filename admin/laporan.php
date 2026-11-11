<?php
$page_title = 'Laporan';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Filter tanggal
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$jenis  = $_GET['jenis']  ?? 'semua';

$dari   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)   ? $dari   : date('Y-m-01');
$sampai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai) ? $sampai : date('Y-m-d');

$where_tgl = "r.created_at BETWEEN '$dari 00:00:00' AND '$sampai 23:59:59'";
$where_pub = $jenis === 'publik' ? "AND r.status='publik'" : ($jenis==='draft' ? "AND r.status='draft'" : '');

$resep_data = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori, j.nama_jenis,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating,
     COUNT(DISTINCT km.id) as jumlah_komentar
     FROM resep r
     LEFT JOIN users u ON r.user_id = u.id
     LEFT JOIN kategori k ON r.id_kategori = k.id
     LEFT JOIN jenis_masakan j ON r.id_jenis = j.id
     LEFT JOIN rating rt ON r.id = rt.id_resep
     LEFT JOIN komentar km ON r.id = km.id_resep
     WHERE $where_tgl $where_pub
     GROUP BY r.id
     ORDER BY r.views DESC");

$resep_list = [];
while ($row = mysqli_fetch_assoc($resep_data)) $resep_list[] = $row;

$stats = [
    'total'  => count($resep_list),
    'publik' => count(array_filter($resep_list, fn($r) => $r['status']==='publik')),
    'draft'  => count(array_filter($resep_list, fn($r) => $r['status']==='draft')),
    'views'  => array_sum(array_column($resep_list, 'views')),
    'rating' => count($resep_list) ? round(array_sum(array_column($resep_list,'rata_rating'))/count($resep_list),1) : 0,
];

// Handle export CSV - SEBELUM include header agar tidak headers already sent
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-resep-'.date('Ymd').'.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['No','Judul','Pembuat','Kategori','Jenis','Kesulitan','Waktu(mnt)','Porsi','Views','Rating','Komentar','Status','Tanggal']);
    $no = 1;
    foreach ($resep_list as $r) {
        fputcsv($out, [
            $no++,
            $r['judul'], $r['nama_user'], $r['nama_kategori'], $r['nama_jenis'],
            ucfirst($r['tingkat_kesulitan']), $r['waktu_masak'], $r['porsi'],
            $r['views'], number_format($r['rata_rating'],1), $r['jumlah_komentar'],
            ucfirst($r['status']), date('d/m/Y', strtotime($r['created_at']))
        ]);
    }
    fclose($out);
    exit;
}

// Include admin header SETELAH semua proses yang butuh redirect/header
require_once 'includes/admin-header.php';?>
<style>
@media (max-width: 768px) {
    .tabel-laporan { display: none !important; }
    .cards-laporan { display: block !important; }

    /* Tombol export/cetak di mobile */
    .laporan-actions { flex-wrap: wrap; gap: 6px !important; }
    .laporan-actions .btn { font-size: 0.72rem !important; padding: 6px 10px !important; }
    .laporan-actions .btn i { margin-right: 3px; }

    /* Filter form di mobile — stack vertikal */
    .laporan-filter-form {
        flex-direction: column !important;
        gap: 0.8rem !important;
    }
    .laporan-filter-form .form-group { flex: none !important; width: 100% !important; min-width: unset !important; }
    .laporan-filter-form select { width: 100% !important; }
    .laporan-filter-form .btn { align-self: flex-start; }
}
@media (min-width: 769px) {
    .tabel-laporan { display: block !important; }
    .cards-laporan { display: none !important; }
}
</style>

<div class="page-header">
    <h1>Laporan <span>Resep</span></h1>
    <div class="laporan-actions" style="display:flex;gap:8px;flex-shrink:0;">
        <a href="?dari=<?= $dari ?>&sampai=<?= $sampai ?>&jenis=<?= $jenis ?>&export=csv"
           class="btn btn-outline btn-sm">
            <i class="fas fa-file-csv"></i> <span class="btn-label">Export CSV</span>
        </a>
        <button onclick="cetakPDF()" class="btn btn-gold btn-sm">
            <i class="fas fa-print"></i> <span class="btn-label">Cetak / PDF</span>
        </button>
    </div>
</div>

<!-- Filter -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <div class="admin-card-header">
        <h3><i class="fas fa-filter"></i> Filter Laporan</h3>
    </div>
    <div style="padding:1.5rem;">
        <form method="GET" class="laporan-filter-form" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:1;min-width:150px;">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:150px;">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
            </div>
            <div class="form-group" style="margin:0;min-width:140px;">
                <label class="form-label">Status</label>
                <select name="jenis" class="form-control">
                    <option value="semua"  <?= $jenis==='semua' ?'selected':'' ?>>Semua</option>
                    <option value="publik" <?= $jenis==='publik'?'selected':'' ?>>Publik</option>
                    <option value="draft"  <?= $jenis==='draft' ?'selected':'' ?>>Draft</option>
                </select>
            </div>
            <button type="submit" class="btn btn-gold">
                <i class="fas fa-search"></i> Terapkan
            </button>
        </form>
    </div>
</div>

<!-- Ringkasan Stats -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem;" id="ringkasan">
    <?php
    $sum = [
        ['label'=>'Total Resep', 'value'=>$stats['total'],               'icon'=>'fa-book-open','bg'=>'rgba(45,106,79,0.1)','color'=>'#2d6a4f'],
        ['label'=>'Publik',      'value'=>$stats['publik'],              'icon'=>'fa-globe',   'bg'=>'rgba(52,152,219,0.1)','color'=>'#2471a3'],
        ['label'=>'Draft',       'value'=>$stats['draft'],               'icon'=>'fa-file-alt','bg'=>'rgba(243,156,18,0.1)','color'=>'#b7770d'],
        ['label'=>'Total Views', 'value'=>number_format($stats['views']),'icon'=>'fa-eye',     'bg'=>'rgba(155,89,182,0.1)','color'=>'#7d3c98'],
        ['label'=>'Avg Rating',  'value'=>$stats['rating'],              'icon'=>'fa-star',    'bg'=>'rgba(245,158,11,0.1)','color'=>'#b7950b'],
    ];
    foreach ($sum as $s):
    ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;">
            <i class="fas <?= $s['icon'] ?>"></i>
        </div>
        <div>
            <div class="stat-number" style="font-size:1.4rem;"><?= $s['value'] ?></div>
            <div class="stat-label"><?= $s['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabel Laporan -->
<div class="admin-card" id="tabel-laporan">
    <div class="admin-card-header">
        <h3><i class="fas fa-table"></i> Daftar Resep — <?= date('d M Y', strtotime($dari)) ?> s/d <?= date('d M Y', strtotime($sampai)) ?></h3>
        <span style="font-size:0.78rem;color:var(--text-3);"><?= $stats['total'] ?> resep</span>
    </div>
    <div style="overflow-x:auto;" class="tabel-laporan">
        <table class="admin-table" id="laporan-table">
            <thead>
                <tr>
                    <th>No</th><th>Judul</th><th>Pembuat</th><th>Kategori</th>
                    <th>Kesulitan</th><th>Views</th><th>Rating</th><th>Komentar</th>
                    <th>Status</th><th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($resep_list)): ?>
                <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-3);">
                    Tidak ada data pada periode ini
                </td></tr>
            <?php else: ?>
            <?php $no=1; foreach ($resep_list as $r): ?>
                <tr>
                    <td style="color:var(--text-3);font-size:0.78rem;font-weight:600;"><?= $no++ ?></td>
                    <td>
                        <div style="font-size:0.85rem;font-weight:600;color:var(--text-1);">
                            <?= htmlspecialchars(mb_strimwidth($r['judul'],0,38,'...')) ?>
                        </div>
                        <div style="font-size:0.7rem;color:var(--text-3);"><?= $r['nama_jenis'] ?> &bull; <?= $r['waktu_masak'] ?> mnt</div>
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-2);font-weight:500;"><?= htmlspecialchars($r['nama_user']) ?></td>
                    <td style="font-size:0.8rem;">
                        <span style="background:rgba(45,106,79,0.08);color:var(--green-2);padding:2px 8px;border-radius:20px;font-size:0.75rem;font-weight:500;">
                            <?= htmlspecialchars($r['nama_kategori']) ?>
                        </span>
                    </td>
                    <td><span class="badge badge-<?= $r['tingkat_kesulitan'] ?>"><?= ucfirst($r['tingkat_kesulitan']) ?></span></td>
                    <td style="font-size:0.85rem;font-weight:600;color:var(--text-1);"><?= number_format($r['views']) ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:3px;">
                            <i class="fas fa-star" style="color:#f59e0b;font-size:0.72rem;"></i>
                            <span style="font-size:0.82rem;font-weight:600;color:var(--text-1);"><?= number_format($r['rata_rating'],1) ?></span>
                            <span style="font-size:0.7rem;color:var(--text-3);">(<?= $r['jumlah_rating'] ?>)</span>
                        </div>
                    </td>
                    <td style="font-size:0.82rem;font-weight:600;color:var(--text-1);text-align:center;"><?= $r['jumlah_komentar'] ?></td>
                    <td><span class="badge badge-<?= $r['status']==='publik'?'publik':'draft' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td style="font-size:0.75rem;color:var(--text-3);white-space:nowrap;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ===== MOBILE CARDS ===== -->
    <div class="cards-laporan" style="padding:1rem;">
    <?php if (empty($resep_list)): ?>
        <div style="text-align:center;padding:3rem;color:var(--text-3);">
            <i class="fas fa-file-alt" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
            Tidak ada data pada periode ini
        </div>
    <?php else: ?>
    <?php $no=1; foreach ($resep_list as $r): ?>
    <div style="background:var(--bg);border-radius:14px;border:1.5px solid var(--border);padding:0.9rem;margin-bottom:0.7rem;">
        <!-- Judul + Status -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:0.6rem;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.88rem;font-weight:700;color:var(--text-1);"><?= $no++ ?>. <?= htmlspecialchars(mb_strimwidth($r['judul'],0,40,'...')) ?></div>
                <div style="font-size:0.72rem;color:var(--text-3);margin-top:2px;"><?= $r['nama_jenis'] ?> &bull; <?= $r['waktu_masak'] ?> mnt</div>
            </div>
            <span class="badge badge-<?= $r['status']==='publik'?'publik':'draft' ?>" style="flex-shrink:0;"><?= ucfirst($r['status']) ?></span>
        </div>
        <!-- Pembuat + Kategori -->
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:0.6rem;">
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.72rem;color:var(--text-2);background:var(--bg-card);padding:4px 10px;border-radius:20px;">
                <i class="fas fa-user" style="font-size:0.65rem;color:var(--text-3);"></i> <?= htmlspecialchars($r['nama_user']) ?>
            </span>
            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.72rem;color:var(--green-2);background:rgba(45,106,79,0.06);padding:4px 10px;border-radius:20px;">
                <i class="fas fa-tag" style="font-size:0.65rem;"></i> <?= htmlspecialchars($r['nama_kategori']) ?>
            </span>
            <span class="badge badge-<?= $r['tingkat_kesulitan'] ?>" style="font-size:0.68rem;"><?= ucfirst($r['tingkat_kesulitan']) ?></span>
        </div>
        <!-- Stats row -->
        <div style="display:flex;justify-content:space-around;text-align:center;padding-top:0.6rem;border-top:1px solid var(--border);">
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--text-1);"><?= number_format($r['views']) ?></div>
                <div style="font-size:0.6rem;color:var(--text-3);"><i class="fas fa-eye"></i> Views</div>
            </div>
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--text-1);"><?= number_format($r['rata_rating'],1) ?></div>
                <div style="font-size:0.6rem;color:var(--text-3);"><i class="fas fa-star" style="color:#f59e0b;"></i> Rating</div>
            </div>
            <div>
                <div style="font-size:0.78rem;font-weight:700;color:var(--text-1);"><?= $r['jumlah_komentar'] ?></div>
                <div style="font-size:0.6rem;color:var(--text-3);"><i class="fas fa-comment"></i> Komentar</div>
            </div>
            <div>
                <div style="font-size:0.72rem;font-weight:600;color:var(--text-2);"><?= date('d/m/y', strtotime($r['created_at'])) ?></div>
                <div style="font-size:0.6rem;color:var(--text-3);"><i class="fas fa-calendar"></i> Tanggal</div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .admin-sidebar,.admin-topbar,.page-header .btn,form,nav{display:none!important;}
    .admin-main{margin-left:0!important;}
    .admin-content{padding:0!important;}
    body{background:#fff!important;}
    .stat-card{break-inside:avoid;}
    .admin-table{font-size:11px;}
    a{text-decoration:none;color:inherit;}
    #print-header{display:block!important;}
}
#print-header{display:none;}
</style>

<div id="print-header" style="margin-bottom:1.5rem;text-align:center;border-bottom:2px solid #2d6a4f;padding-bottom:1rem;">
    <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:#2d6a4f;margin:0;">LAPORAN DapurIn</h2>
    <p style="font-size:0.85rem;color:#666;margin:4px 0 0;">
        Periode: <?= date('d M Y', strtotime($dari)) ?> s/d <?= date('d M Y', strtotime($sampai)) ?>
        &nbsp;|&nbsp; Dicetak: <?= date('d M Y H:i') ?>
        &nbsp;|&nbsp; Total: <?= $stats['total'] ?> resep
    </p>
</div>

<script>function cetakPDF(){window.print();}</script>

<?php require_once 'includes/admin-footer.php'; ?>