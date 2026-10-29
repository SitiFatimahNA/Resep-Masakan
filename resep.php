<?php
session_start();
require_once 'config/koneksi.php';

$page_title = 'Daftar Resep';

$q         = trim($_GET['q'] ?? '');
$kategori  = trim($_GET['kategori'] ?? '');
$jenis     = trim($_GET['jenis'] ?? '');
$kesulitan = trim($_GET['kesulitan'] ?? '');
$sort      = trim($_GET['sort'] ?? 'terbaru');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 9;
$offset    = ($page - 1) * $per_page;

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
    $where[]  = "EXISTS (SELECT 1 FROM resep_kategori rk2 JOIN kategori k2 ON rk2.id_kategori=k2.id WHERE rk2.id_resep=r.id AND k2.slug=?)";
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

$count_sql = "SELECT COUNT(DISTINCT r.id) as total
              FROM resep r
              LEFT JOIN kategori k ON r.id_kategori=k.id
              LEFT JOIN jenis_masakan j ON r.id_jenis=j.id
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

$sql = "SELECT r.*, u.nama as nama_user,
        k.nama_kategori, k.slug as kat_slug,
        j.nama_jenis,
        COALESCE(AVG(rt.nilai),0) as rata_rating,
        COUNT(DISTINCT rt.id) as jumlah_rating
        FROM resep r
        LEFT JOIN users u ON r.user_id=u.id
        LEFT JOIN kategori k ON r.id_kategori=k.id
        LEFT JOIN jenis_masakan j ON r.id_jenis=j.id
        LEFT JOIN rating rt ON r.id=rt.id_resep
        WHERE $where_sql
        GROUP BY r.id
        ORDER BY $order_sql
        LIMIT ? OFFSET ?";

$resep_list    = [];
$params_page   = array_merge($params, [$per_page, $offset]);
$types_page    = $types . 'ii';
$stmt2 = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt2, $types_page, ...$params_page);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
while ($row = mysqli_fetch_assoc($result2)) $resep_list[] = $row;
mysqli_stmt_close($stmt2);

// Multi-kategori map
$resep_kategori_map = [];
if (!empty($resep_list)) {
    $ids = implode(',', array_column($resep_list, 'id'));
    $rk  = mysqli_query($koneksi,
        "SELECT rk.id_resep, k.nama_kategori, k.slug
         FROM resep_kategori rk JOIN kategori k ON rk.id_kategori=k.id
         WHERE rk.id_resep IN ($ids)");
    while ($r = mysqli_fetch_assoc($rk)) $resep_kategori_map[$r['id_resep']][] = $r;
    foreach ($resep_list as $r) {
        if (empty($resep_kategori_map[$r['id']]) && $r['nama_kategori'])
            $resep_kategori_map[$r['id']][] = ['nama_kategori'=>$r['nama_kategori'],'slug'=>$r['kat_slug']];
    }
}

$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$jenis_list    = mysqli_query($koneksi, "SELECT * FROM jenis_masakan ORDER BY nama_jenis ASC");

require_once 'includes/header.php';
?>

<!-- ===== PAGE HERO ===== -->
<div style="
    background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);
    padding:3rem 0 5rem;
    position:relative;
    overflow:hidden;
">
    <div style="position:absolute;top:-100px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <p style="font-size:0.72rem;font-weight:600;color:#52b788;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">
                    <a href="/resep-masakan/home.php" style="color:#52b788;text-decoration:none;">Beranda</a>
                    <i class="fas fa-chevron-right" style="font-size:0.55rem;margin:0 6px;"></i>
                    Resep
                    <?php if ($kategori): ?>
                    <i class="fas fa-chevron-right" style="font-size:0.55rem;margin:0 6px;"></i>
                    <?= htmlspecialchars(ucwords(str_replace('-',' ',$kategori))) ?>
                    <?php endif; ?>
                </p>
                <h1 style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#fff;font-weight:700;letter-spacing:-0.5px;margin:0;">
                    <?php if ($q): ?>
                        Hasil "<span style="color:#95d5b2;"><?= htmlspecialchars($q) ?></span>"
                    <?php elseif ($kategori): ?>
                        Resep <span style="color:#95d5b2;"><?= htmlspecialchars(ucwords(str_replace('-',' ',$kategori))) ?></span>
                    <?php else: ?>
                        Semua <span style="color:#95d5b2;">Resep</span>
                    <?php endif; ?>
                </h1>
                <p style="color:rgba(255,255,255,0.5);font-size:0.875rem;margin-top:6px;">
                    <?= number_format($total_data) ?> resep ditemukan
                </p>
            </div>

            <!-- Quick sort pills -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php
                $sorts = ['terbaru'=>'Terbaru','populer'=>'Populer','rating'=>'Rating','terlama'=>'Terlama'];
                foreach ($sorts as $sv => $sl):
                    $params_url = $_GET; $params_url['sort'] = $sv; unset($params_url['page']);
                    $active = $sort === $sv;
                ?>
                <a href="/resep-masakan/resep.php?<?= http_build_query($params_url) ?>"
                   style="
                    display:inline-flex;align-items:center;gap:5px;
                    padding:7px 14px;border-radius:20px;font-size:0.78rem;font-weight:600;
                    text-decoration:none;transition:all 0.2s;
                    <?= $active
                        ? 'background:rgba(82,183,136,0.9);color:#fff;'
                        : 'background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.65);border:1px solid rgba(255,255,255,0.15);' ?>
                   "><?= $sl ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Wave -->
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:40px;">
            <path d="M0,20 C360,40 1080,0 1440,20 L1440,40 L0,40 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div style="background:#f4f7f4;min-height:60vh;padding:2rem 0 5rem;">
<div class="container">
<div style="display:grid;grid-template-columns:260px 1fr;gap:1.8rem;align-items:start;">

    <!-- ===== SIDEBAR ===== -->
    <div style="position:sticky;top:90px;">
        <div style="background:#fff;border-radius:20px;border:1.5px solid #e4ede6;overflow:hidden;box-shadow:0 2px 12px rgba(45,106,79,0.07);">
            <!-- Header sidebar -->
            <div style="padding:1.2rem 1.5rem;background:linear-gradient(135deg,#f0faf4,#fff);border-bottom:1px solid #e4ede6;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.85rem;font-weight:700;color:#1a4731;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-sliders-h" style="color:var(--green);"></i> Filter Resep
                </span>
                <?php if ($q || $kategori || $jenis || $kesulitan || $sort !== 'terbaru'): ?>
                <a href="/resep-masakan/resep.php" style="font-size:0.72rem;color:var(--danger);text-decoration:none;font-weight:600;">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </div>

            <div style="padding:1.2rem;">
                <form method="GET" action="">
                    <!-- Search -->
                    <div style="margin-bottom:1.2rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Cari</label>
                        <div style="position:relative;">
                            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#8a9e87;font-size:0.82rem;"></i>
                            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
                                   placeholder="Nama resep..."
                                   style="width:100%;padding:9px 12px 9px 34px;background:#f4f7f4;border:1.5px solid #e4ede6;border-radius:10px;font-size:0.85rem;color:#1a2e1a;outline:none;transition:border-color 0.2s;"
                                   onfocus="this.style.borderColor='var(--green)'"
                                   onblur="this.style.borderColor='#e4ede6'">
                        </div>
                    </div>

                    <!-- Kategori -->
                    <div style="margin-bottom:1.2rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Kategori</label>
                        <select name="kategori"
                                style="width:100%;padding:9px 12px;background:#f4f7f4;border:1.5px solid #e4ede6;border-radius:10px;font-size:0.85rem;color:#1a2e1a;outline:none;cursor:pointer;"
                                onchange="this.style.borderColor='var(--green)'">
                            <option value="">Semua Kategori</option>
                            <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                            <option value="<?= $kat['slug'] ?>" <?= $kategori===$kat['slug']?'selected':'' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Jenis -->
                    <div style="margin-bottom:1.2rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Jenis</label>
                        <select name="jenis"
                                style="width:100%;padding:9px 12px;background:#f4f7f4;border:1.5px solid #e4ede6;border-radius:10px;font-size:0.85rem;color:#1a2e1a;outline:none;cursor:pointer;">
                            <option value="">Semua Jenis</option>
                            <?php while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                            <option value="<?= $j['slug'] ?>" <?= $jenis===$j['slug']?'selected':'' ?>>
                                <?= htmlspecialchars($j['nama_jenis']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Kesulitan -->
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;">Kesulitan</label>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php
                            $levels = ['' => 'Semua', 'mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit'];
                            $colors = ['mudah'=>'#2d6a4f','sedang'=>'#d68910','sulit'=>'#c0392b'];
                            foreach ($levels as $lv => $ll):
                                $active_lv = $kesulitan === $lv;
                            ?>
                            <label style="
                                display:flex;align-items:center;gap:8px;
                                padding:8px 12px;border-radius:10px;cursor:pointer;
                                background:<?= $active_lv ? 'rgba(45,106,79,0.08)' : 'transparent' ?>;
                                border:1.5px solid <?= $active_lv ? 'rgba(45,106,79,0.3)' : 'transparent' ?>;
                                transition:all 0.2s;
                            ">
                                <input type="radio" name="kesulitan" value="<?= $lv ?>"
                                       <?= $active_lv ? 'checked' : '' ?>
                                       style="accent-color:var(--green);width:14px;height:14px;">
                                <span style="font-size:0.85rem;font-weight:500;color:<?= $lv ? ($colors[$lv]??'#1a2e1a') : '#1a2e1a' ?>;">
                                    <?= $ll ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

                    <button type="submit" style="
                        width:100%;padding:11px;
                        background:linear-gradient(135deg,#2d6a4f,#40916c);
                        color:#fff;border:none;border-radius:12px;
                        font-size:0.875rem;font-weight:600;cursor:pointer;
                        display:flex;align-items:center;justify-content:center;gap:8px;
                        transition:all 0.3s;box-shadow:0 4px 12px rgba(45,106,79,0.3);
                        font-family:inherit;
                    " onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 18px rgba(45,106,79,0.4)'"
                       onmouseout="this.style.transform='';this.style.boxShadow='0 4px 12px rgba(45,106,79,0.3)'">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== KONTEN ===== -->
    <div>
        <?php if (empty($resep_list)): ?>
        <!-- Empty state -->
        <div style="background:#fff;border-radius:20px;border:1.5px solid #e4ede6;padding:5rem 2rem;text-align:center;">
            <div style="width:72px;height:72px;background:#f0faf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                <i class="fas fa-search" style="font-size:1.8rem;color:#95d5b2;"></i>
            </div>
            <h3 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:#1a2e1a;margin-bottom:0.5rem;">Resep tidak ditemukan</h3>
            <p style="color:#8a9e87;margin-bottom:1.5rem;">Coba kata kunci atau filter yang berbeda</p>
            <a href="/resep-masakan/resep.php" style="
                display:inline-flex;align-items:center;gap:8px;
                background:linear-gradient(135deg,#2d6a4f,#40916c);
                color:#fff;padding:10px 24px;border-radius:20px;
                font-size:0.875rem;font-weight:600;text-decoration:none;
            ">Lihat Semua Resep</a>
        </div>

        <?php else: ?>
        <!-- Grid -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem;margin-bottom:2rem;">
            <?php foreach ($resep_list as $resep): ?>
            <div onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'"
                 style="
                    background:#fff;border-radius:20px;overflow:hidden;
                    border:1.5px solid #e4ede6;cursor:pointer;
                    transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
                    box-shadow:0 2px 8px rgba(45,106,79,0.06);
                    display:flex;flex-direction:column;
                 "
                 onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(45,106,79,0.14)';this.style.borderColor='#95d5b2'"
                 onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.06)';this.style.borderColor='#e4ede6'">

                <!-- Gambar -->
                <div style="position:relative;overflow:hidden;height:190px;flex-shrink:0;">
                    <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                         onmouseover="this.style.transform='scale(1.05)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">

                    <!-- Badge kesulitan -->
                    <span style="
                        position:absolute;top:12px;left:12px;
                        background:<?= $resep['tingkat_kesulitan']==='mudah'?'#2d6a4f':($resep['tingkat_kesulitan']==='sedang'?'#d68910':'#c0392b') ?>;
                        color:#fff;padding:4px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;
                        box-shadow:0 2px 8px rgba(0,0,0,0.2);
                    "><?= ucfirst($resep['tingkat_kesulitan']) ?></span>

                    <!-- Waktu masak -->
                    <span style="
                        position:absolute;bottom:12px;left:12px;
                        background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);
                        color:#fff;padding:4px 10px;border-radius:20px;font-size:0.68rem;
                    "><i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt</span>

                    <!-- Favorit btn -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button onclick="event.stopPropagation(); toggleFavorit(<?= $resep['id'] ?>, this)"
                            style="
                                position:absolute;top:12px;right:12px;
                                background:rgba(255,255,255,0.9);border:none;
                                width:32px;height:32px;border-radius:50%;
                                cursor:pointer;display:flex;align-items:center;justify-content:center;
                                color:#e74c3c;font-size:0.85rem;
                                box-shadow:0 2px 8px rgba(0,0,0,0.15);
                                transition:all 0.2s;
                            "
                            onmouseover="this.style.transform='scale(1.1)'"
                            onmouseout="this.style.transform=''">
                        <i class="far fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Konten -->
                <div style="padding:1.1rem;flex:1;display:flex;flex-direction:column;">
                    <!-- Kategori labels -->
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px;">
                        <?php
                        $kats = $resep_kategori_map[$resep['id']] ?? [];
                        foreach (array_slice($kats,0,2) as $kat_item):
                        ?>
                        <span style="font-size:0.68rem;font-weight:600;color:var(--green);background:#f0faf4;padding:2px 8px;border-radius:20px;">
                            <?= htmlspecialchars($kat_item['nama_kategori']) ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if (count($kats) > 2): ?>
                        <span style="font-size:0.68rem;color:#8a9e87;padding:2px 6px;">+<?= count($kats)-2 ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Judul -->
                    <h3 style="
                        font-family:'Playfair Display',serif;
                        font-size:0.95rem;font-weight:700;
                        color:#1a2e1a;margin-bottom:0.6rem;
                        line-height:1.4;flex:1;
                        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
                    "><?= htmlspecialchars($resep['judul']) ?></h3>

                    <!-- Meta -->
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.8rem;flex-wrap:wrap;">
                        <span style="font-size:0.72rem;color:#8a9e87;display:flex;align-items:center;gap:4px;">
                            <i class="fas fa-users"></i> <?= $resep['porsi'] ?> porsi
                        </span>
                        <span style="font-size:0.72rem;color:#8a9e87;display:flex;align-items:center;gap:4px;">
                            <i class="fas fa-eye"></i> <?= number_format($resep['views']) ?>
                        </span>
                    </div>

                    <!-- Footer card -->
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:0.8rem;border-top:1px solid #f0f5f0;">
                        <div style="display:flex;align-items:center;gap:5px;">
                            <div style="display:flex;gap:1px;color:#f59e0b;font-size:0.72rem;">
                                <?php
                                $rat=round($resep['rata_rating']);
                                for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#e0e0e0;"></i>';
                                ?>
                            </div>
                            <span style="font-size:0.7rem;color:#8a9e87;">(<?= $resep['jumlah_rating'] ?>)</span>
                        </div>
                        <div style="font-size:0.72rem;color:#8a9e87;display:flex;align-items:center;gap:4px;max-width:100px;overflow:hidden;">
                            <i class="fas fa-user-circle" style="flex-shrink:0;"></i>
                            <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($resep['nama_user']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_page > 1): ?>
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:1rem;">
            <?php
            $pu = $_GET; unset($pu['page']);
            $base_url = '/resep-masakan/resep.php?'.http_build_query($pu).'&page=';
            ?>
            <?php if ($page > 1): ?>
            <a href="<?= $base_url.($page-1) ?>" style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;transition:all 0.2s;"
               onmouseover="this.style.borderColor='var(--green)';this.style.color='var(--green)'"
               onmouseout="this.style.borderColor='#e4ede6';this.style.color='#4a6741'">
                <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
            </a>
            <?php endif; ?>

            <?php for ($p=max(1,$page-2); $p<=min($total_page,$page+2); $p++): ?>
            <a href="<?= $base_url.$p ?>" style="
                display:flex;align-items:center;justify-content:center;
                width:38px;height:38px;border-radius:10px;text-decoration:none;
                font-size:0.875rem;font-weight:600;transition:all 0.2s;
                <?= $p===$page
                    ? 'background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;border:1.5px solid transparent;box-shadow:0 4px 12px rgba(45,106,79,0.3);'
                    : 'background:#fff;border:1.5px solid #e4ede6;color:#4a6741;' ?>
            "
            <?php if ($p !== $page): ?>
            onmouseover="this.style.borderColor='var(--green)';this.style.color='var(--green)'"
            onmouseout="this.style.borderColor='#e4ede6';this.style.color='#4a6741'"
            <?php endif; ?>>
                <?= $p ?>
            </a>
            <?php endfor; ?>

            <?php if ($page < $total_page): ?>
            <a href="<?= $base_url.($page+1) ?>" style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;transition:all 0.2s;"
               onmouseover="this.style.borderColor='var(--green)';this.style.color='var(--green)'"
               onmouseout="this.style.borderColor='#e4ede6';this.style.color='#4a6741'">
                <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
            </a>
            <?php endif; ?>

            <span style="font-size:0.78rem;color:#8a9e87;margin-left:8px;">
                <?= $page ?> / <?= $total_page ?> halaman
            </span>
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
            btn.style.color = '#e74c3c';
        } else {
            icon.classList.replace('fas', 'far');
            btn.style.color = '#e74c3c';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
