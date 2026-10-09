<?php
session_start();
require_once 'config/koneksi.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: /resep-masakan/resep.php'); exit; }

// Ambil data resep
$stmt = mysqli_prepare($koneksi,
    "SELECT r.*, u.nama as nama_user, u.foto as foto_user, u.id as id_pemilik,
     k.nama_kategori, k.slug as kat_slug,
     j.nama_jenis,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id = u.id
     LEFT JOIN kategori k ON r.id_kategori = k.id
     LEFT JOIN jenis_masakan j ON r.id_jenis = j.id
     LEFT JOIN rating rt ON r.id = rt.id_resep
     WHERE r.slug = ? AND r.status = 'publik'
     GROUP BY r.id");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$resep = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$resep) { header('Location: /resep-masakan/resep.php'); exit; }

// Tambah views
mysqli_query($koneksi, "UPDATE resep SET views = views + 1 WHERE id = {$resep['id']}");

// Ambil bahan
$bahan_result = mysqli_query($koneksi, "SELECT * FROM bahan_resep WHERE id_resep = {$resep['id']} ORDER BY id ASC");
$bahan_list = [];
while ($b = mysqli_fetch_assoc($bahan_result)) $bahan_list[] = $b;

// Ambil langkah
$langkah_result = mysqli_query($koneksi, "SELECT * FROM langkah_resep WHERE id_resep = {$resep['id']} ORDER BY urutan ASC");
$langkah_list = [];
while ($l = mysqli_fetch_assoc($langkah_result)) $langkah_list[] = $l;

// Ambil komentar
$komentar_result = mysqli_query($koneksi,
    "SELECT k.*, u.nama, u.foto FROM komentar k
     LEFT JOIN users u ON k.id_user = u.id
     WHERE k.id_resep = {$resep['id']} AND k.status = 'aktif'
     ORDER BY k.created_at DESC");
$komentar_list = [];
while ($k = mysqli_fetch_assoc($komentar_result)) $komentar_list[] = $k;

// Cek favorit user
$is_favorit = false;
$rating_user = 0;
if (isset($_SESSION['user_id'])) {
    $fav_stmt = mysqli_prepare($koneksi, "SELECT id FROM favorit WHERE id_resep=? AND id_user=?");
    mysqli_stmt_bind_param($fav_stmt, 'ii', $resep['id'], $_SESSION['user_id']);
    mysqli_stmt_execute($fav_stmt);
    mysqli_stmt_store_result($fav_stmt);
    $is_favorit = mysqli_stmt_num_rows($fav_stmt) > 0;
    mysqli_stmt_close($fav_stmt);

    $rat_stmt = mysqli_prepare($koneksi, "SELECT nilai FROM rating WHERE id_resep=? AND id_user=?");
    mysqli_stmt_bind_param($rat_stmt, 'ii', $resep['id'], $_SESSION['user_id']);
    mysqli_stmt_execute($rat_stmt);
    $rat_row = mysqli_fetch_assoc(mysqli_stmt_get_result($rat_stmt));
    $rating_user = $rat_row['nilai'] ?? 0;
    mysqli_stmt_close($rat_stmt);
}

// Handle POST komentar
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['komentar'])) {
    if (!isset($_SESSION['user_id'])) {
        $pesan = 'error:Silakan login untuk berkomentar.';
    } else {
        $isi = trim($_POST['komentar']);
        if (strlen($isi) < 3) {
            $pesan = 'error:Komentar terlalu pendek.';
        } else {
            $ks = mysqli_prepare($koneksi, "INSERT INTO komentar (id_resep, id_user, komentar) VALUES (?,?,?)");
            mysqli_stmt_bind_param($ks, 'iis', $resep['id'], $_SESSION['user_id'], $isi);
            mysqli_stmt_execute($ks);
            mysqli_stmt_close($ks);
            header("Location: /resep-masakan/detail-resep.php?slug=" . urlencode($slug) . "#komentar");
            exit;
        }
    }
}

// Handle rating POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (isset($_SESSION['user_id'])) {
        $nilai = (int)$_POST['rating'];
        if ($nilai >= 1 && $nilai <= 5) {
            $rs = mysqli_prepare($koneksi,
                "INSERT INTO rating (id_resep, id_user, nilai) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE nilai=?");
            mysqli_stmt_bind_param($rs, 'iiii', $resep['id'], $_SESSION['user_id'], $nilai, $nilai);
            mysqli_stmt_execute($rs);
            mysqli_stmt_close($rs);
            header("Location: /resep-masakan/detail-resep.php?slug=" . urlencode($slug));
            exit;
        }
    }
}

$page_title = htmlspecialchars($resep['judul']);
require_once 'includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container">

    <!-- Breadcrumb -->
    <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1.5rem;" class="animate">
        <a href="/resep-masakan/home.php" style="color:var(--green);">Beranda</a>
        <i class="fas fa-chevron-right" style="font-size:0.7rem; margin:0 6px;"></i>
        <a href="/resep-masakan/resep.php" style="color:var(--green);">Resep</a>
        <i class="fas fa-chevron-right" style="font-size:0.7rem; margin:0 6px;"></i>
        <span><?= htmlspecialchars($resep['judul']) ?></span>
    </div>

    <div style="display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start;">

        <!-- ===== KONTEN UTAMA ===== -->
        <div>
            <!-- Thumbnail -->
            <div style="border-radius:var(--radius); overflow:hidden; margin-bottom:2rem; position:relative;" class="animate">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     alt="<?= htmlspecialchars($resep['judul']) ?>"
                     style="width:100%; height:420px; object-fit:cover;"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <div style="position:absolute; top:16px; left:16px; display:flex; gap:8px;">
                    <span class="badge badge-<?= $resep['tingkat_kesulitan'] ?>"><?= ucfirst($resep['tingkat_kesulitan']) ?></span>
                    <span class="badge badge-gold"><?= htmlspecialchars($resep['nama_kategori']) ?></span>
                </div>
            </div>

            <!-- Judul & Info -->
            <div class="animate" style="background:#fff; border-radius:var(--radius); padding:2rem; border:1px solid var(--border); margin-bottom:1.5rem;">
                <div style="display:flex; align-items:start; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--text-primary); margin-bottom:0.8rem;">
                            <?= htmlspecialchars($resep['judul']) ?>
                        </h1>
                        <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; margin-bottom:1rem;">
                            <span style="font-size:0.85rem; color:var(--text-muted); display:flex; align-items:center; gap:5px;">
                                <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($resep['foto_user'] ?? 'default.png') ?>"
                                     style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:2px solid var(--green-light);"
                                     onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                                <?= htmlspecialchars($resep['nama_user']) ?>
                            </span>
                            <span style="font-size:0.82rem; color:var(--text-muted);">
                                <i class="fas fa-calendar"></i>
                                <?= date('d M Y', strtotime($resep['created_at'])) ?>
                            </span>
                            <span style="font-size:0.82rem; color:var(--text-muted);">
                                <i class="fas fa-eye"></i> <?= number_format($resep['views']) ?> dilihat
                            </span>
                        </div>
                        <p style="color:var(--text-secondary); line-height:1.8; font-size:0.95rem;">
                            <?= nl2br(htmlspecialchars($resep['deskripsi'])) ?>
                        </p>
                    </div>

                    <!-- Aksi -->
                    <div style="display:flex; flex-direction:column; gap:8px; min-width:140px;">
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <button onclick="toggleFavorit()" id="btn-favorit" class="btn <?= $is_favorit ? 'btn-danger' : 'btn-outline' ?>" style="border-radius:8px;">
                            <i class="<?= $is_favorit ? 'fas' : 'far' ?> fa-heart"></i>
                            <?= $is_favorit ? 'Tersimpan' : 'Simpan' ?>
                        </button>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $resep['id_pemilik'] || $_SESSION['role'] === 'admin')): ?>
                        <a href="/resep-masakan/user/edit-resep.php?id=<?= $resep['id'] ?>" class="btn btn-dark" style="border-radius:8px; text-align:center;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Grid -->
                <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border);">
                    <div style="text-align:center; padding:1rem; background:var(--bg-secondary); border-radius:var(--radius-sm);">
                        <i class="fas fa-clock" style="color:var(--green); font-size:1.2rem; margin-bottom:4px; display:block;"></i>
                        <div style="font-weight:600; font-size:1rem;"><?= $resep['waktu_masak'] ?> mnt</div>
                        <div style="font-size:0.72rem; color:var(--text-muted);">Waktu Masak</div>
                    </div>
                    <div style="text-align:center; padding:1rem; background:var(--bg-secondary); border-radius:var(--radius-sm);">
                        <i class="fas fa-users" style="color:var(--green); font-size:1.2rem; margin-bottom:4px; display:block;"></i>
                        <div style="font-weight:600; font-size:1rem;"><?= $resep['porsi'] ?></div>
                        <div style="font-size:0.72rem; color:var(--text-muted);">Porsi</div>
                    </div>
                    <div style="text-align:center; padding:1rem; background:var(--bg-secondary); border-radius:var(--radius-sm);">
                        <i class="fas fa-signal" style="color:var(--green); font-size:1.2rem; margin-bottom:4px; display:block;"></i>
                        <div style="font-weight:600; font-size:1rem;"><?= ucfirst($resep['tingkat_kesulitan']) ?></div>
                        <div style="font-size:0.72rem; color:var(--text-muted);">Kesulitan</div>
                    </div>
                    <div style="text-align:center; padding:1rem; background:var(--bg-secondary); border-radius:var(--radius-sm);">
                        <i class="fas fa-star" style="color:#f4a124; font-size:1.2rem; margin-bottom:4px; display:block;"></i>
                        <div style="font-weight:600; font-size:1rem;"><?= number_format($resep['rata_rating'],1) ?></div>
                        <div style="font-size:0.72rem; color:var(--text-muted);"><?= $resep['jumlah_rating'] ?> rating</div>
                    </div>
                </div>
            </div>

            <!-- Bahan -->
            <div class="animate" style="background:#fff; border-radius:var(--radius); padding:2rem; border:1px solid var(--border); margin-bottom:1.5rem;">
                <h2 style="font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--text-primary); margin-bottom:1.2rem; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-list-ul" style="color:var(--green);"></i> Bahan-bahan
                </h2>
                <?php if (empty($bahan_list)): ?>
                    <p style="color:var(--text-muted);">Belum ada bahan.</p>
                <?php else: ?>
                <ul style="list-style:none; display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                    <?php foreach ($bahan_list as $b): ?>
                    <li style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:var(--bg-secondary); border-radius:var(--radius-sm); font-size:0.9rem;">
                        <i class="fas fa-check-circle" style="color:var(--green); font-size:0.8rem; flex-shrink:0;"></i>
                        <span><strong><?= htmlspecialchars($b['jumlah']) ?> <?= htmlspecialchars($b['satuan'] ?? '') ?></strong> <?= htmlspecialchars($b['nama_bahan']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Langkah -->
            <div class="animate" style="background:#fff; border-radius:var(--radius); padding:2rem; border:1px solid var(--border); margin-bottom:1.5rem;">
                <h2 style="font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--text-primary); margin-bottom:1.5rem; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-list-ol" style="color:var(--green);"></i> Cara Memasak
                </h2>
                <?php if (empty($langkah_list)): ?>
                    <p style="color:var(--text-muted);">Belum ada langkah.</p>
                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:1.5rem;">
                    <?php foreach ($langkah_list as $l): ?>
                    <div style="display:flex; gap:1.2rem; align-items:start;">
                        <div style="width:36px; height:36px; background:var(--green); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; flex-shrink:0;">
                            <?= $l['urutan'] ?>
                        </div>
                        <div style="flex:1; padding-top:6px;">
                            <p style="color:var(--text-secondary); line-height:1.8; font-size:0.95rem; margin:0;">
                                <?= nl2br(htmlspecialchars($l['instruksi'])) ?>
                            </p>
                            <?php if ($l['foto_langkah']): ?>
                            <img src="/resep-masakan/uploads/langkah/<?= htmlspecialchars($l['foto_langkah']) ?>"
                                 alt="Langkah <?= $l['urutan'] ?>"
                                 style="margin-top:10px; width:100%; max-width:400px; border-radius:var(--radius-sm); object-fit:cover;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Komentar -->
            <div id="komentar" class="animate" style="background:#fff; border-radius:var(--radius); padding:2rem; border:1px solid var(--border);">
                <h2 style="font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--text-primary); margin-bottom:1.5rem; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-comments" style="color:var(--green);"></i>
                    Komentar <span style="font-size:1rem; color:var(--text-muted);">(<?= count($komentar_list) ?>)</span>
                </h2>

                <?php if ($pesan): ?>
                    <?php [$type, $msg] = explode(':', $pesan, 2); ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : 'success' ?>">
                        <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" style="margin-bottom:2rem;">
                    <div class="form-group">
                        <label class="form-label">Tulis komentar</label>
                        <textarea name="komentar" class="form-control" rows="3"
                                  placeholder="Bagikan pengalamanmu..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-paper-plane"></i> Kirim Komentar
                    </button>
                </form>
                <?php else: ?>
                <div style="background:var(--bg-secondary); border-radius:var(--radius-sm); padding:1.2rem; margin-bottom:2rem; text-align:center; font-size:0.9rem; color:var(--text-muted);">
                    <a href="/resep-masakan/login.php" style="color:var(--green); font-weight:500;">Login</a> untuk berkomentar
                </div>
                <?php endif; ?>

                <!-- List Komentar -->
                <?php if (empty($komentar_list)): ?>
                <p style="color:var(--text-muted); text-align:center; padding:2rem 0;">Belum ada komentar. Jadilah yang pertama!</p>
                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:1.2rem;">
                    <?php foreach ($komentar_list as $k): ?>
                    <div style="display:flex; gap:12px; padding:1rem; background:var(--bg-secondary); border-radius:var(--radius-sm);">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($k['foto'] ?? 'default.png') ?>"
                             style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--green-light);"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <div style="flex:1;">
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                <strong style="font-size:0.875rem; color:var(--text-primary);"><?= htmlspecialchars($k['nama']) ?></strong>
                                <span style="font-size:0.75rem; color:var(--text-muted);"><?= date('d M Y', strtotime($k['created_at'])) ?></span>
                            </div>
                            <p style="font-size:0.875rem; color:var(--text-secondary); margin:0; line-height:1.6;">
                                <?= nl2br(htmlspecialchars($k['komentar'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== SIDEBAR ===== -->
        <div style="position:sticky; top:90px; display:flex; flex-direction:column; gap:1.5rem;">

            <!-- Rating -->
            <div class="animate" style="background:#fff; border-radius:var(--radius); padding:1.5rem; border:1px solid var(--border);">
                <h3 style="font-size:1rem; font-weight:600; margin-bottom:1.2rem; color:var(--text-primary);">
                    <i class="fas fa-star" style="color:#f4a124;"></i> Beri Rating
                </h3>
                <div style="text-align:center; margin-bottom:1rem;">
                    <div style="font-size:2.5rem; font-weight:700; color:var(--text-primary);">
                        <?= number_format($resep['rata_rating'],1) ?>
                    </div>
                    <div style="display:flex; justify-content:center; gap:4px; color:#f4a124; font-size:1.2rem; margin:4px 0;">
                        <?php for ($s=1;$s<=5;$s++) echo $s<=round($resep['rata_rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                    </div>
                    <div style="font-size:0.8rem; color:var(--text-muted);"><?= $resep['jumlah_rating'] ?> penilaian</div>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST">
                    <div style="display:flex; justify-content:center; gap:6px; margin-bottom:1rem;" id="star-input">
                        <?php for ($s=1;$s<=5;$s++): ?>
                        <label style="cursor:pointer; font-size:1.5rem; color:<?= $s<=$rating_user ? '#f4a124' : '#ddd' ?>;" 
                               onmouseover="highlightStars(<?= $s ?>)" onmouseout="resetStars()">
                            <input type="radio" name="rating" value="<?= $s ?>" style="display:none;" <?= $s==$rating_user ? 'checked':'' ?>>
                            <i class="<?= $s<=$rating_user ? 'fas' : 'far' ?> fa-star" id="star-<?= $s ?>"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <button type="submit" class="btn btn-gold w-100" style="font-size:0.85rem;">
                        <i class="fas fa-check"></i> <?= $rating_user ? 'Update Rating' : 'Beri Rating' ?>
                    </button>
                </form>
                <?php else: ?>
                <p style="text-align:center; font-size:0.85rem; color:var(--text-muted);">
                    <a href="/resep-masakan/login.php" style="color:var(--green);">Login</a> untuk memberi rating
                </p>
                <?php endif; ?>
            </div>

            <!-- Info Resep -->
            <div class="animate animate-delay-2" style="background:#fff; border-radius:var(--radius); padding:1.5rem; border:1px solid var(--border);">
                <h3 style="font-size:1rem; font-weight:600; margin-bottom:1rem; color:var(--text-primary);">
                    <i class="fas fa-info-circle" style="color:var(--green);"></i> Info Resep
                </h3>
                <div style="display:flex; flex-direction:column; gap:0.6rem; font-size:0.875rem;">
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border);">
                        <span style="color:var(--text-muted);">Kategori</span>
                        <a href="/resep-masakan/resep.php?kategori=<?= $resep['kat_slug'] ?>" style="color:var(--green); font-weight:500;"><?= htmlspecialchars($resep['nama_kategori']) ?></a>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border);">
                        <span style="color:var(--text-muted);">Jenis</span>
                        <span style="color:var(--text-primary);"><?= htmlspecialchars($resep['nama_jenis']) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--border);">
                        <span style="color:var(--text-muted);">Bahan</span>
                        <span style="color:var(--text-primary);"><?= count($bahan_list) ?> item</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0;">
                        <span style="color:var(--text-muted);">Langkah</span>
                        <span style="color:var(--text-primary);"><?= count($langkah_list) ?> langkah</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<script>
// Toggle favorit AJAX
function toggleFavorit() {
    fetch('/resep-masakan/user/ajax-favorit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_resep=<?= $resep['id'] ?>'
    })
    .then(r => r.json())
    .then(data => {
        const btn = document.getElementById('btn-favorit');
        if (data.status === 'added') {
            btn.innerHTML = '<i class="fas fa-heart"></i> Tersimpan';
            btn.className = 'btn btn-danger';
        } else {
            btn.innerHTML = '<i class="far fa-heart"></i> Simpan';
            btn.className = 'btn btn-outline';
        }
    });
}

// Star rating hover
let selectedRating = <?= $rating_user ?>;
function highlightStars(n) {
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (star) star.className = i <= n ? 'fas fa-star' : 'far fa-star';
        if (star) star.parentElement.style.color = i <= n ? '#f4a124' : '#ddd';
    }
}
function resetStars() {
    highlightStars(selectedRating);
}
document.querySelectorAll('#star-input input').forEach(input => {
    input.addEventListener('change', () => { selectedRating = parseInt(input.value); });
});
</script>

<?php require_once 'includes/footer.php'; ?>
