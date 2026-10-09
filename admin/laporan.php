<?php
require_once 'includes/admin-header.php';
require_once '../config/koneksi.php';

$page_title = 'Laporan';

// Filter tanggal
$dari  = $_GET['dari']  ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$jenis = $_GET['jenis'] ?? 'semua';

// Validasi tanggal
$dari   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)   ? $dari   : date('Y-m-01');
$sampai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai) ? $sampai : date('Y-m-d');

// WHERE tanggal
$where_tgl = "r.created_at BETWEEN '$dari 00:00:00' AND '$sampai 23:59:59'";
$where_pub = $jenis === 'publik' ? "AND r.status='publik'" : ($jenis==='draft' ? "AND r.status='draft'" : '');

// Data laporan resep
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

// Statistik ringkasan
$stats = [
    'total'   => count($resep_list),
    'publik'  => count(array_filter($resep_list, fn($r) => $r['status']==='publik')),
    'draft'   => count(array_filter($resep_list, fn($r) => $r['status']==='draft')),
    'views'   => array_sum(array_column($resep_list, 'views')),
    'rating'  => count($resep_list) ? round(array_sum(array_column($resep_list,'rata_rating'))/count($resep_list),1) : 0,
];

// Handle export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-resep-'.date('Ymd').'.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM untuk Excel
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
?>

<div class="page-header">
    <h1>Laporan <span>Resep</span></h1>
    <div style="display:flex; gap:8px;">
        <!-- Export CSV -->
        <a href="?dari=<?= $dari ?>&sampai=<?= $sampai ?>&jenis=<?= $jenis ?>&export=csv"
           class="btn btn-outline btn-sm">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        <!-- Print PDF -->
        <button onclick="cetakPDF()" class="btn btn-gold btn-sm">
            <i class="fas fa-print"></i> Cetak / PDF
        </button>
    </div>
</div>

<!-- Filter -->
<div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:1.5rem;" class="animate">
    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:flex-end;">
        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
        </div>
        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
        </div>
        <div class="form-group" style="margin:0; min-width:140px;">
            <label class="form-label">Status</label>
            <select name="jenis" class="form-control">
                <option value="semua"  <?= $jenis==='semua' ?'selected':'' ?>>Semua</option>
                <option value="publik" <?= $jenis==='publik'?'selected':'' ?>>Publik</option>
                <option value="draft"  <?= $jenis==='draft' ?'selected':'' ?>>Draft</option>
            </select>
        </div>
        <button type="submit" class="btn btn-gold" style="margin-bottom:0;">
            <i class="fas fa-filter"></i> Terapkan
        </button>
    </form>
</div>

<!-- Ringkasan -->
<div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1rem; margin-bottom:1.5rem;" class="animate" id="ringkasan">
    <?php
    $sum = [
        ['label'=>'Total Resep',   'value'=>$stats['total'],          'icon'=>'fa-utensils', 'color'=>'var(--green)'],
        ['label'=>'Publik',        'value'=>$stats['publik'],         'icon'=>'fa-globe',    'color'=>'#3498db'],
        ['label'=>'Draft',         'value'=>$stats['draft'],          'icon'=>'fa-file-alt', 'color'=>'#f39c18'],
        ['label'=>'Total Views',   'value'=>number_format($stats['views']), 'icon'=>'fa-eye','color'=>'#9b59b6'],
        ['label'=>'Avg Rating',    'value'=>$stats['rating'],         'icon'=>'fa-star',     'color'=>'#f1c40f'],
    ];
    foreach ($sum as $s):
    ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:rgba(64,145,108,0.08); color:<?= $s['color'] ?>;">
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
<div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate" id="tabel-laporan">
    <div style="padding:1rem 1.5rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <h3 style="font-size:0.95rem; font-weight:600; color:var(--text-primary); margin:0;">
            Daftar Resep — <?= date('d M Y', strtotime($dari)) ?> s/d <?= date('d M Y', strtotime($sampai)) ?>
        </h3>
        <span style="font-size:0.82rem; color:var(--text-muted);"><?= $stats['total'] ?> resep</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table" id="laporan-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Resep</th>
                    <th>Pembuat</th>
                    <th>Kategori</th>
                    <th>Kesulitan</th>
                    <th>Views</th>
                    <th>Rating</th>
                    <th>Komentar</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($resep_list)): ?>
                <tr><td colspan="10" style="text-align:center; padding:3rem; color:var(--text-muted);">
                    Tidak ada data pada periode ini
                </td></tr>
            <?php else: ?>
            <?php $no=1; foreach ($resep_list as $r): ?>
                <tr>
                    <td style="color:var(--text-muted); font-size:0.8rem;"><?= $no++ ?></td>
                    <td>
                        <div style="font-size:0.85rem; font-weight:500; color:var(--text-primary);">
                            <?= htmlspecialchars(mb_strimwidth($r['judul'],0,40,'...')) ?>
                        </div>
                        <div style="font-size:0.72rem; color:var(--text-muted);">
                            <?= $r['nama_jenis'] ?> &bull; <?= $r['waktu_masak'] ?> mnt
                        </div>
                    </td>
                    <td style="font-size:0.82rem;"><?= htmlspecialchars($r['nama_user']) ?></td>
                    <td style="font-size:0.82rem;"><?= htmlspecialchars($r['nama_kategori']) ?></td>
                    <td><span class="badge badge-<?= $r['tingkat_kesulitan'] ?>"><?= ucfirst($r['tingkat_kesulitan']) ?></span></td>
                    <td style="font-size:0.82rem;"><?= number_format($r['views']) ?></td>
                    <td>
                        <span style="color:#f4a124; font-size:0.82rem;">
                            <i class="fas fa-star"></i> <?= number_format($r['rata_rating'],1) ?>
                        </span>
                        <span style="font-size:0.72rem; color:var(--text-muted);">(<?= $r['jumlah_rating'] ?>)</span>
                    </td>
                    <td style="font-size:0.82rem; text-align:center;"><?= $r['jumlah_komentar'] ?></td>
                    <td>
                        <span style="padding:3px 8px; border-radius:20px; font-size:0.75rem; font-weight:500;
                                     background:<?= $r['status']==='publik'?'rgba(45,106,79,0.12)':'rgba(243,156,18,0.12)' ?>;
                                     color:<?= $r['status']==='publik'?'var(--green-dark)':'#9a6c00' ?>;">
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                        <?= date('d M Y', strtotime($r['created_at'])) ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .admin-sidebar, .admin-topbar, .page-header .btn,
    form, .animate, nav { display: none !important; }
    .admin-main { margin-left: 0 !important; }
    .admin-content { padding: 0 !important; }
    body { background: #fff !important; }
    .stat-card { break-inside: avoid; }
    #tabel-laporan { page-break-before: auto; }
    .admin-table { font-size: 11px; }
    a { text-decoration: none; color: inherit; }

    /* Header cetak */
    #print-header { display: block !important; }
}
#print-header { display: none; }
</style>

<!-- Header khusus print -->
<div id="print-header" style="margin-bottom:1.5rem; text-align:center; border-bottom:2px solid #2d6a4f; padding-bottom:1rem;">
    <h2 style="font-family:'Playfair Display',serif; font-size:1.4rem; color:#2d6a4f; margin:0;">
        LAPORAN RESEP MASAKAN
    </h2>
    <p style="font-size:0.85rem; color:#666; margin:4px 0 0;">
        Periode: <?= date('d M Y', strtotime($dari)) ?> s/d <?= date('d M Y', strtotime($sampai)) ?>
        &nbsp;|&nbsp; Dicetak: <?= date('d M Y H:i') ?>
        &nbsp;|&nbsp; Total: <?= $stats['total'] ?> resep
    </p>
</div>

<script>
function cetakPDF() {
    window.print();
}
</script>

<?php require_once 'includes/admin-footer.php'; ?>
