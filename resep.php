<?php
session_start();
require_once 'config/koneksi.php';

$page_title = 'Daftar Resep';

// Parameter filter & pencarian
$q         = trim($_GET['q'] ?? '');
$kategori  = trim($_GET['kategori'] ?? '');
$jenis     = trim($_GET['jenis'] ?? '');
$kesulitan = trim($_GET['kesulitan'] ?? '');
$sort      = trim($_GET['sort'] ?? 'terbaru');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 9;
$offset    = ($page - 1) * $per_page;

// Bangun query dengan prepared statement
$where   = ["r.status = 'publik'"];
$params  = [];
$types   = '';

if ($q !== '') {
    $where[]  = "(r.judul LIKE ? OR r.deskripsi LIKE ?)";
    $like     = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if ($kategori !== '') {
    $where[]  = "k.slug = ?";
    $params[] = $kategori;
    $types   .= 's';
}

if ($jenis !== '') {
    $where[]  = "j.slug = ?";
    $params[] = $jenis;
    $types   .= 's';
}

if ($kesulitan !== '') {
    $where[]  = "r.tingkat_kesulitan = ?";
    $params[] = $kesulitan;
    $types   .= 's';
}

$where_sql = implode(' AND ', $where);

$order_sql = match($sort) {
    'populer'  => 'r.views DESC',
    'rating'   => 'rata_rating DESC',
    'terlama'  => 'r.created_at ASC',
    default    => 'r.created_at DESC',
};

// Hitung total data
$count_sql = "SELECT COUNT(DISTINCT r.id) as total
              FROM resep r
              LEFT JOIN kategori k ON r.id_kategori = k.id
              LEFT JOIN jenis_masakan j ON r.id_jenis = j.id
              WHERE $where_sql";

$total_data = 0;
if (!empty($params)) {
    $stmt = mysqli_prepare($koneksi, $count_sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $total_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);
} else {
    $total_data = mysqli_fetch_assoc(mysqli_query($koneksi, $count_sql))['total'];
}

$total_page = ceil($total_data / $per_page);

// Ambil data resep
$sql = "SELECT r.*, u.nama as nama_user, u.foto as foto_user,
        k.nama_kategori, k.slug as kat_slug,
        j.nama_jenis,
        COALESCE(AVG(rt.nilai), 0) as rata_rating,
        COUNT(DISTINCT rt.id) as jumlah_rating
        FROM resep r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN kategori k ON r.id_kategori = k.id
        LEFT JOIN jenis_masakan j ON r.id_jenis = j.id
        LEFT JOIN rating rt ON r.id = rt.id_resep
        WHERE $where_sql
        GROUP BY r.id
        ORDER BY $order_sql
        LIMIT ? OFFSET ?";

$resep_list = [];
$params_page   = array_merge($params, [$per_page, $offset]);
$types_page    = $types . 'ii';

$stmt2 = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt2, $types_page, ...$params_page);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($result2)) {
    $resep_list[] = $row;
}
mysqli_stmt_close($stmt2);

// Ambil semua kategori untuk filter
$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$jenis_list    = mysqli_query($koneksi, "SELECT * FROM jenis_masakan ORDER BY nama_jenis ASC");

require_once 'includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container">

    <!-- Page Header -->
    <div style="margin-bottom:2rem; padding-top:1rem;" class="animate">
        <h1 style="font-family:'Playfair Display',serif; font-size:2rem; color:var(--text-primary);">
            <?php if ($q): ?>
                Hasil pencarian: "<span style="color:var(--green);"><?= htmlspecialchars($q) ?></span>"
            <?php elseif ($kategori): ?>
                Resep <span style="color:var(--green);"><?= htmlspecialchars(ucwords(str_replace('-',' ',$kategori))) ?></span>
            <?php else: ?>
                Semua <span style="color:var(--green);">Resep</span>
            <?php endif; ?>
        </h1>
        <p style="color:var(--text-muted); font-size:0.9rem;">
            Menampilkan <?= $total_data ?> resep
        </p>
        <div class="divider"></div>
    </div>

    <div style="display:grid; grid-template-columns:260px 1fr; gap:2rem; align-items:start;">

        <!-- ========== SIDEBAR FILTER ========== -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; position:sticky; top:90px;" class="animate">
            <form method="GET" action="">
                <!-- Cari -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-search"></i> Cari Resep</label>
                    <input type="text" name="q" class="form-control"
                           placeholder="Nama resep..."
                           value="<?= htmlspecialchars($q) ?>">
                </div>

                <!-- Kategori -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-tag"></i> Kategori</label>
                    <select name="kategori" class="form-control">
                        <option value="">Semua Kategori</option>
                        <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                        <option value="<?= $kat['slug'] ?>"
                            <?= $kategori === $kat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kat['nama_kategori']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Jenis -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-utensils"></i> Jenis</label>
                    <select name="jenis" class="form-control">
                        <option value="">Semua Jenis</option>
                        <?php while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                        <option value="<?= $j['slug'] ?>"
                            <?= $jenis === $j['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($j['nama_jenis']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Kesulitan -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-signal"></i> Kesulitan</label>
                    <select name="kesulitan" class="form-control">
                        <option value="">Semua</option>
                        <option value="mudah"  <?= $kesulitan==='mudah'  ? 'selected':'' ?>>Mudah</option>
                        <option value="sedang" <?= $kesulitan==='sedang' ? 'selected':'' ?>>Sedang</option>
                        <option value="sulit"  <?= $kesulitan==='sulit'  ? 'selected':'' ?>>Sulit</option>
                    </select>
                </div>

                <!-- Urutkan -->
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-sort"></i> Urutkan</label>
                    <select name="sort" class="form-control">
                        <option value="terbaru" <?= $sort==='terbaru' ? 'selected':'' ?>>Terbaru</option>
                        <option value="populer" <?= $sort==='populer' ? 'selected':'' ?>>Terpopuler</option>
                        <option value="rating"  <?= $sort==='rating'  ? 'selected':'' ?>>Rating Tertinggi</option>
                        <option value="terlama" <?= $sort==='terlama' ? 'selected':'' ?>>Terlama</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-gold w-100">
                    <i class="fas fa-filter"></i> Terapkan Filter
                </button>

                <?php if ($q || $kategori || $jenis || $kesulitan || $sort !== 'terbaru'): ?>
                <a href="/resep-masakan/resep.php" class="btn btn-dark w-100" style="margin-top:8px; text-align:center;">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ========== KONTEN RESEP ========== -->
        <div>
            <?php if (empty($resep_list)): ?>
            <!-- Kosong -->
            <div style="text-align:center; padding:5rem 2rem; background:#fff; border-radius:var(--radius); border:1px solid var(--border);">
                <i class="fas fa-search" style="font-size:3rem; color:var(--border); margin-bottom:1rem; display:block;"></i>
                <h3 style="color:var(--text-secondary); margin-bottom:0.5rem;">Resep tidak ditemukan</h3>
                <p style="color:var(--text-muted); font-size:0.9rem;">Coba kata kunci atau filter yang berbeda</p>
                <a href="/resep-masakan/resep.php" class="btn btn-gold" style="margin-top:1.5rem;">Lihat Semua Resep</a>
            </div>

            <?php else: ?>
            <!-- Grid Resep -->
            <div class="grid-3" style="margin-bottom:2rem;">
                <?php foreach ($resep_list as $i => $resep): ?>
                <div class="card animate animate-delay-<?= ($i % 3) + 1 ?>">
                    <!-- Thumbnail -->
                    <div style="overflow:hidden; position:relative;">
                        <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                             alt="<?= htmlspecialchars($resep['judul']) ?>"
                             class="card-img"
                             onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                        <span class="badge badge-<?= $resep['tingkat_kesulitan'] ?>"
                              style="position:absolute; top:12px; left:12px;">
                            <?= ucfirst($resep['tingkat_kesulitan']) ?>
                        </span>
                        <span style="position:absolute; top:12px; right:12px; background:rgba(255,255,255,0.92); padding:4px 10px; border-radius:20px; font-size:0.72rem; color:var(--green-dark); font-weight:500;">
                            <?= htmlspecialchars($resep['nama_kategori']) ?>
                        </span>
                        <!-- Favorit btn (jika login) -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <button onclick="toggleFavorit(<?= $resep['id'] ?>, this)"
                                style="position:absolute; bottom:12px; right:12px; background:rgba(255,255,255,0.9); border:none; width:34px; height:34px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--danger); font-size:0.9rem; transition:var(--transition);"
                                title="Simpan ke favorit">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>
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
                                <?php
                                $rat = round($resep['rata_rating']);
                                for ($s=1;$s<=5;$s++) {
                                    echo $s<=$rat ? '<i class="fas fa-star"></i>' : '<i class="far fa-star empty"></i>';
                                }
                                ?>
                            </div>
                            <span style="font-size:0.75rem; color:var(--text-muted);">(<?= $resep['jumlah_rating'] ?>)</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span style="font-size:0.78rem; color:var(--text-muted); display:flex; align-items:center; gap:5px;">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars($resep['nama_user']) ?>
                        </span>
                        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>"
                           class="btn btn-gold btn-sm">
                            Lihat <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($total_page > 1): ?>
            <div class="pagination">
                <?php
                // Bangun base URL untuk pagination
                $params_url = $_GET;
                unset($params_url['page']);
                $base_url = '/resep-masakan/resep.php?' . http_build_query($params_url) . '&page=';
                ?>

                <?php if ($page > 1): ?>
                <a href="<?= $base_url . ($page-1) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end   = min($total_page, $page + 2);
                for ($p = $start; $p <= $end; $p++):
                ?>
                <a href="<?= $base_url . $p ?>"
                   class="<?= $p === $page ? 'active' : '' ?>">
                    <?= $p ?>
                </a>
                <?php endfor; ?>

                <?php if ($page < $total_page): ?>
                <a href="<?= $base_url . ($page+1) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
function toggleFavorit(id, btn) {
    fetch('/resep-masakan/user/ajax-favorit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_resep=' + id
    })
    .then(r => r.json())
    .then(data => {
        const icon = btn.querySelector('i');
        if (data.status === 'added') {
            icon.classList.replace('far', 'fas');
            btn.style.color = 'var(--danger)';
        } else {
            icon.classList.replace('fas', 'far');
            btn.style.color = 'var(--text-muted)';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
