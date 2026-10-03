<?php
session_start();
require_once 'config/koneksi.php';

$page_title = 'Beranda';

// Ambil resep terbaru (6 resep)
$resep_terbaru = [];
$sql = "SELECT r.*, u.nama as nama_user, k.nama_kategori, k.icon as kategori_icon,
        COALESCE(AVG(rt.nilai), 0) as rata_rating,
        COUNT(DISTINCT rt.id) as jumlah_rating
        FROM resep r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN kategori k ON r.id_kategori = k.id
        LEFT JOIN rating rt ON r.id = rt.id_resep
        WHERE r.status = 'publik'
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT 6";
$result = mysqli_query($koneksi, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $resep_terbaru[] = $row;
    }
}

// Ambil resep populer (4 resep berdasarkan views)
$resep_populer = [];
$sql2 = "SELECT r.*, u.nama as nama_user, k.nama_kategori, k.icon as kategori_icon,
         COALESCE(AVG(rt.nilai), 0) as rata_rating,
         COUNT(DISTINCT rt.id) as jumlah_rating
         FROM resep r
         LEFT JOIN users u ON r.user_id = u.id
         LEFT JOIN kategori k ON r.id_kategori = k.id
         LEFT JOIN rating rt ON r.id = rt.id_resep
         WHERE r.status = 'publik'
         GROUP BY r.id
         ORDER BY r.views DESC
         LIMIT 4";
$result2 = mysqli_query($koneksi, $sql2);
if ($result2) {
    while ($row = mysqli_fetch_assoc($result2)) {
        $resep_populer[] = $row;
    }
}

// Ambil semua kategori
$kategori_list = [];
$sql3 = "SELECT k.*, COUNT(r.id) as jumlah_resep
         FROM kategori k
         LEFT JOIN resep r ON k.id = r.id_kategori AND r.status = 'publik'
         GROUP BY k.id
         ORDER BY k.nama_kategori ASC";
$result3 = mysqli_query($koneksi, $sql3);
if ($result3) {
    while ($row = mysqli_fetch_assoc($result3)) {
        $kategori_list[] = $row;
    }
}

// Statistik
$total_resep = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM resep WHERE status='publik'"))['total'] ?? 0;
$total_user  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='user'"))['total'] ?? 0;
$total_kategori = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kategori"))['total'] ?? 0;

require_once 'includes/header.php';
?>

<!-- =====================================================
     HERO SECTION
     ===================================================== -->
<section class="hero">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center;">
            <!-- Hero Content -->
            <div class="hero-content">
                <div class="hero-tag"><i class="fas fa-crown"></i> Platform Resep Terlengkap</div>
                <h1 class="hero-title">
                    Temukan Resep <span>Masakan</span> Terbaik
                </h1>
                <p class="hero-desc">
                    Jelajahi ribuan resep masakan dari berbagai kategori. Dari sarapan pagi hingga dessert malam, semuanya ada di sini.
                </p>
                <div class="hero-actions">
                    <a href="/resep-masakan/resep.php" class="btn btn-gold btn-lg">
                        <i class="fas fa-utensils"></i> Jelajahi Resep
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="/resep-masakan/register.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-user-plus"></i> Bergabung
                    </a>
                    <?php else: ?>
                    <a href="/resep-masakan/user/tambah-resep.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-plus"></i> Tambah Resep
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="number counter" data-target="<?= $total_resep ?>">
                            <?= $total_resep ?>+
                        </div>
                        <div class="label">Resep</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number counter" data-target="<?= $total_user ?>">
                            <?= $total_user ?>+
                        </div>
                        <div class="label">Member</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number counter" data-target="<?= $total_kategori ?>">
                            <?= $total_kategori ?>+
                        </div>
                        <div class="label">Kategori</div>
                    </div>
                </div>
            </div>

            <!-- Hero Visual -->
            <div style="position:relative; text-align:center;" class="animate animate-delay-2">
                <!-- Frame foto utama -->
                <div style="position:relative; width:380px; margin:0 auto;">
                    <!-- Border dekorasi -->
                    <div style="
                        position:absolute; top:-15px; left:-15px;
                        width:100%; height:100%;
                        border:2px solid rgba(201,168,76,0.4);
                        border-radius:20px;
                        z-index:0;
                    "></div>
                    <!-- Foto utama -->
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80"
                         alt="Masakan Lezat"
                         style="
                            width:100%;
                            height:420px;
                            object-fit:cover;
                            border-radius:20px;
                            position:relative;
                            z-index:1;
                            border:3px solid rgba(201,168,76,0.3);
                            animation: float 5s ease-in-out infinite;
                         ">

                    <!-- Card info mengambang kiri bawah -->
                    <div style="
                        position:absolute; bottom:20px; left:-30px;
                        background:rgba(26,26,26,0.95);
                        border:1px solid var(--border-gold);
                        border-radius:14px;
                        padding:12px 16px;
                        z-index:2;
                        backdrop-filter:blur(10px);
                        animation: float 4s ease-in-out infinite 0.5s;
                        text-align:left;
                    ">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="width:36px; height:36px; background:rgba(201,168,76,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-fire" style="color:var(--gold);"></i>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--text-muted);">Resep Populer</div>
                                <div style="font-size:0.85rem; color:var(--text-primary); font-weight:600;">+<?= $total_resep ?> Resep</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card rating mengambang kanan atas -->
                    <div style="
                        position:absolute; top:20px; right:-30px;
                        background:rgba(26,26,26,0.95);
                        border:1px solid var(--border-gold);
                        border-radius:14px;
                        padding:12px 16px;
                        z-index:2;
                        backdrop-filter:blur(10px);
                        animation: float 4s ease-in-out infinite 1s;
                        text-align:left;
                    ">
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:4px;">Rating</div>
                        <div style="display:flex; gap:3px; color:var(--gold); font-size:0.9rem;">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-primary); font-weight:600; margin-top:2px;">5.0 / 5.0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div style="position:absolute; bottom:2rem; left:50%; transform:translateX(-50%); text-align:center; animation: fadeIn 1s ease 1s both;">
        <div style="color:var(--text-muted); font-size:0.75rem; letter-spacing:2px; text-transform:uppercase; margin-bottom:8px;">Scroll</div>
        <div style="width:1px; height:40px; background:linear-gradient(to bottom, var(--gold), transparent); margin:0 auto;"></div>
    </div>
</section>

<!-- =====================================================
     KATEGORI SECTION
     ===================================================== -->
<section class="section" style="background:var(--bg-secondary);">
    <div class="container">
        <div class="section-header animate">
            <div>
                <h2 class="section-title">Jelajahi <span>Kategori</span></h2>
                <div class="divider"></div>
                <p class="section-subtitle">Temukan resep berdasarkan bahan dan jenis masakan favoritmu</p>
            </div>
            <a href="/resep-masakan/kategori.php" class="btn btn-outline">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div style="display:grid; grid-template-columns:repeat(6,1fr); gap:1rem;">
            <?php
            // Map kategori slug ke nama file gambar
            $kat_img = [
                'ayam'        => 'kat-ayam.jpg',
                'daging-sapi' => 'kat-daging.jpg',
                'ikan'        => 'kat-ikan.jpg',
                'seafood'     => 'kat-seafood.jpg',
                'sayuran'     => 'kat-sayuran.jpg',
                'tahu-tempe'  => 'kat-tahu.jpg',
                'telur'       => 'kat-telur.jpg',
                'kambing'     => 'kat-kambing.jpg',
                'bebek'       => 'kat-bebek.jpg',
                'buah-buahan' => 'kat-buah.jpg',
                'mie-pasta'   => 'kat-mie.jpg',
                'nasi'        => 'kat-nasi.jpg',
            ];
            foreach ($kategori_list as $i => $kat):
                $img = $kat_img[$kat['slug']] ?? 'kat-default.jpg';
            ?>
            <a href="/resep-masakan/resep.php?kategori=<?= urlencode($kat['slug']) ?>"
               class="animate animate-delay-<?= ($i % 4) + 1 ?>"
               style="
                text-decoration:none;
                background:var(--bg-card);
                border:1px solid var(--border);
                border-radius:var(--radius);
                overflow:hidden;
                transition:var(--transition);
                display:block;
                position:relative;
               "
               onmouseover="this.style.borderColor='var(--gold)';this.style.transform='translateY(-5px)';this.style.boxShadow='0 10px 30px rgba(201,168,76,0.15)'"
               onmouseout="this.style.borderColor='var(--border)';this.style.transform='translateY(0)';this.style.boxShadow='none'"
            >
                <!-- Gambar kategori -->
                <div style="width:100%; height:90px; overflow:hidden;">
                    <img src="/resep-masakan/assets/images/kategori/<?= $img ?>"
                         alt="<?= htmlspecialchars($kat['nama_kategori']) ?>"
                         style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease;"
                         onmouseover="this.style.transform='scale(1.1)'"
                         onmouseout="this.style.transform='scale(1)'"
                         onerror="this.parentElement.style.background='var(--bg-hover)';this.style.display='none'">
                </div>
                <!-- Teks -->
                <div style="padding:0.7rem 0.5rem; text-align:center;">
                    <div style="font-size:0.8rem; color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                    <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;"><?= $kat['jumlah_resep'] ?> resep</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- =====================================================
     RESEP TERBARU
     ===================================================== -->
<section class="section">
    <div class="container">
        <div class="section-header animate">
            <div>
                <h2 class="section-title">Resep <span>Terbaru</span></h2>
                <div class="divider"></div>
                <p class="section-subtitle">Resep-resep segar yang baru saja ditambahkan</p>
            </div>
            <a href="/resep-masakan/resep.php" class="btn btn-outline">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($resep_terbaru)): ?>
        <div style="text-align:center; padding:4rem; color:var(--text-muted);">
            <i class="fas fa-utensils" style="font-size:4rem; color:var(--border); display:block; margin-bottom:1rem;"></i>
            <p>Belum ada resep. <a href="/resep-masakan/<?= isset($_SESSION['user_id']) ? 'user/tambah-resep.php' : 'register.php' ?>">Jadilah yang pertama!</a></p>
        </div>
        <?php else: ?>
        <div class="grid-3">
            <?php foreach ($resep_terbaru as $i => $resep): ?>
            <div class="card animate animate-delay-<?= ($i % 3) + 1 ?>">
                <!-- Thumbnail -->
                <div style="overflow:hidden; position:relative;">
                    <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                         alt="<?= htmlspecialchars($resep['judul']) ?>"
                         class="card-img"
                         onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                    <!-- Badge kesulitan -->
                    <span class="badge badge-<?= $resep['tingkat_kesulitan'] ?>"
                          style="position:absolute; top:12px; left:12px;">
                        <?= ucfirst($resep['tingkat_kesulitan']) ?>
                    </span>
                    <!-- Kategori -->
                    <span style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.7); padding:4px 10px; border-radius:20px; font-size:0.75rem; color:var(--gold);">
                        <i class="fas fa-tag"></i> <?= htmlspecialchars($resep['nama_kategori']) ?>
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
                        <span><i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> menit</span>
                        <span><i class="fas fa-users"></i> <?= $resep['porsi'] ?> porsi</span>
                        <span><i class="fas fa-eye"></i> <?= number_format($resep['views']) ?></span>
                    </div>

                    <!-- Rating -->
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                        <div class="stars">
                            <?php
                            $rating = round($resep['rata_rating']);
                            for ($s = 1; $s <= 5; $s++) {
                                echo $s <= $rating
                                    ? '<i class="fas fa-star"></i>'
                                    : '<i class="far fa-star empty"></i>';
                            }
                            ?>
                        </div>
                        <span style="font-size:0.78rem; color:var(--text-muted);">
                            (<?= $resep['jumlah_rating'] ?>)
                        </span>
                    </div>
                </div>

                <div class="card-footer">
                    <span style="font-size:0.8rem; color:var(--text-muted);">
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
        <?php endif; ?>
    </div>
</section>

<!-- =====================================================
     RESEP POPULER
     ===================================================== -->
<?php if (!empty($resep_populer)): ?>
<section class="section" style="background:var(--bg-secondary);">
    <div class="container">
        <div class="section-header animate">
            <div>
                <h2 class="section-title">Resep <span>Populer</span></h2>
                <div class="divider"></div>
                <p class="section-subtitle">Resep yang paling banyak dilihat dan disukai</p>
            </div>
            <a href="/resep-masakan/populer.php" class="btn btn-outline">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid-4">
            <?php foreach ($resep_populer as $i => $resep): ?>
            <div class="card animate animate-delay-<?= $i + 1 ?>">
                <div style="overflow:hidden; position:relative;">
                    <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                         alt="<?= htmlspecialchars($resep['judul']) ?>"
                         class="card-img"
                         onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                    <!-- Rank badge -->
                    <div style="
                        position:absolute; top:12px; left:12px;
                        width:32px; height:32px;
                        background:var(--gold);
                        border-radius:50%;
                        display:flex; align-items:center; justify-content:center;
                        font-weight:700; font-size:0.85rem; color:#0f0f0f;
                    ">#<?= $i + 1 ?></div>
                </div>

                <div class="card-body">
                    <h3 class="card-title">
                        <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>"
                           style="color:var(--text-primary);">
                            <?= htmlspecialchars($resep['judul']) ?>
                        </a>
                    </h3>
                    <div class="card-meta">
                        <span><i class="fas fa-eye"></i> <?= number_format($resep['views']) ?> views</span>
                        <span><i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt</span>
                    </div>
                    <div class="stars" style="font-size:0.8rem;">
                        <?php
                        $rating = round($resep['rata_rating']);
                        for ($s = 1; $s <= 5; $s++) {
                            echo $s <= $rating
                                ? '<i class="fas fa-star"></i>'
                                : '<i class="far fa-star empty"></i>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================
     CTA SECTION
     ===================================================== -->
<?php if (!isset($_SESSION['user_id'])): ?>
<section class="section">
    <div class="container">
        <div class="animate" style="
            background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
            border: 1px solid var(--border-gold);
            border-radius: 24px;
            padding: 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        ">
            <!-- Dekorasi -->
            <div style="position:absolute; top:-50px; right:-50px; width:200px; height:200px; background:radial-gradient(circle, rgba(201,168,76,0.1) 0%, transparent 70%); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-50px; left:-50px; width:200px; height:200px; background:radial-gradient(circle, rgba(201,168,76,0.08) 0%, transparent 70%); border-radius:50%;"></div>

            <i class="fas fa-hat-chef" style="font-size:3rem; color:var(--gold); display:block; margin-bottom:1rem;"></i>
            <h2 class="section-title" style="margin-bottom:1rem;">
                Punya Resep <span>Spesial?</span>
            </h2>
            <p style="color:var(--text-secondary); max-width:500px; margin:0 auto 2rem;">
                Bergabung dan bagikan resep andalanmu kepada jutaan pecinta kuliner di seluruh Indonesia.
            </p>
            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                <a href="/resep-masakan/register.php" class="btn btn-gold btn-lg">
                    <i class="fas fa-user-plus"></i> Daftar Gratis
                </a>
                <a href="/resep-masakan/login.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Sudah Punya Akun
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
