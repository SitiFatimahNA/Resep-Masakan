<?php
session_start();
require_once 'config/koneksi.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: /resep-masakan/resep.php'); exit; }

$is_admin        = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$user_id_session = (int)($_SESSION['user_id'] ?? 0);

$stmt = mysqli_prepare($koneksi,
    "SELECT r.*, u.nama as nama_user, u.foto as foto_user, u.id as id_pemilik,
     k.nama_kategori, k.slug as kat_slug, j.nama_jenis,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id=u.id
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN jenis_masakan j ON r.id_jenis=j.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE r.slug=? GROUP BY r.id");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$resep = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$resep) { header('Location: /resep-masakan/resep.php'); exit; }

if ($resep['status'] === 'draft') {
    if (!$is_admin && $resep['id_pemilik'] !== $user_id_session) {
        header('Location: /resep-masakan/resep.php'); exit;
    }
}

mysqli_query($koneksi, "UPDATE resep SET views=views+1 WHERE id={$resep['id']}");

// Ambil data pendukung
$bahan_list   = [];
$br = mysqli_query($koneksi, "SELECT * FROM bahan_resep WHERE id_resep={$resep['id']} ORDER BY id ASC");
while ($b = mysqli_fetch_assoc($br)) $bahan_list[] = $b;

$langkah_list = [];
$lr = mysqli_query($koneksi, "SELECT * FROM langkah_resep WHERE id_resep={$resep['id']} ORDER BY urutan ASC");
while ($l = mysqli_fetch_assoc($lr)) $langkah_list[] = $l;

$alat_list = [];
$ar = mysqli_query($koneksi, "SELECT * FROM alat_masak WHERE id_resep={$resep['id']} ORDER BY id ASC");
while ($a = mysqli_fetch_assoc($ar)) $alat_list[] = $a;

$nutrisi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM nutrisi WHERE id_resep={$resep['id']}")) ?? null;

$kategori_resep = [];
$krr = mysqli_query($koneksi, "SELECT k.* FROM resep_kategori rk JOIN kategori k ON rk.id_kategori=k.id WHERE rk.id_resep={$resep['id']}");
while ($kr = mysqli_fetch_assoc($krr)) $kategori_resep[] = $kr;
if (empty($kategori_resep) && $resep['id_kategori']) {
    $fb = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kategori WHERE id={$resep['id_kategori']}"));
    if ($fb) $kategori_resep[] = $fb;
}

// Komentar
$komentar_list = [];
$kmr = mysqli_query($koneksi,
    "SELECT k.*, u.nama, u.foto FROM komentar k LEFT JOIN users u ON k.id_user=u.id
     WHERE k.id_resep={$resep['id']} AND k.status='aktif' AND (k.parent_id IS NULL OR k.parent_id=0)
     ORDER BY k.created_at ASC");
while ($k = mysqli_fetch_assoc($kmr)) {
    $rpr = mysqli_query($koneksi,
        "SELECT k.*, u.nama, u.foto FROM komentar k LEFT JOIN users u ON k.id_user=u.id
         WHERE k.parent_id={$k['id']} AND k.status='aktif' ORDER BY k.created_at ASC");
    $k['replies'] = [];
    while ($rp = mysqli_fetch_assoc($rpr)) $k['replies'][] = $rp;
    $komentar_list[] = $k;
}

// Favorit & rating user
$is_favorit  = false;
$rating_user = 0;
if (isset($_SESSION['user_id'])) {
    $fs = mysqli_prepare($koneksi, "SELECT id FROM favorit WHERE id_resep=? AND id_user=?");
    mysqli_stmt_bind_param($fs,'ii',$resep['id'],$_SESSION['user_id']);
    mysqli_stmt_execute($fs); mysqli_stmt_store_result($fs);
    $is_favorit = mysqli_stmt_num_rows($fs) > 0;
    mysqli_stmt_close($fs);

    $rs2 = mysqli_prepare($koneksi, "SELECT nilai FROM rating WHERE id_resep=? AND id_user=?");
    mysqli_stmt_bind_param($rs2,'ii',$resep['id'],$_SESSION['user_id']);
    mysqli_stmt_execute($rs2);
    $rv = mysqli_fetch_assoc(mysqli_stmt_get_result($rs2));
    $rating_user = $rv['nilai'] ?? 0;
    mysqli_stmt_close($rs2);
}

// Handle POST
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['komentar'])) {
    if (!isset($_SESSION['user_id'])) { $pesan = 'error:Silakan login untuk berkomentar.'; }
    else {
        $isi = trim($_POST['komentar']);
        $pid = (int)($_POST['parent_id'] ?? 0);
        if (strlen($isi) < 3) { $pesan = 'error:Komentar terlalu pendek.'; }
        else {
            if ($pid > 0) {
                $ks = mysqli_prepare($koneksi, "INSERT INTO komentar (id_resep,id_user,parent_id,komentar) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($ks,'iiis',$resep['id'],$_SESSION['user_id'],$pid,$isi);
            } else {
                $ks = mysqli_prepare($koneksi, "INSERT INTO komentar (id_resep,id_user,komentar) VALUES (?,?,?)");
                mysqli_stmt_bind_param($ks,'iis',$resep['id'],$_SESSION['user_id'],$isi);
            }
            mysqli_stmt_execute($ks); mysqli_stmt_close($ks);
            header("Location: /resep-masakan/detail-resep.php?slug=".urlencode($slug)."#komentar"); exit;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    if (isset($_SESSION['user_id'])) {
        $nilai = (int)$_POST['rating'];
        if ($nilai >= 1 && $nilai <= 5) {
            $rs3 = mysqli_prepare($koneksi,
                "INSERT INTO rating (id_resep,id_user,nilai) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nilai=?");
            mysqli_stmt_bind_param($rs3,'iiii',$resep['id'],$_SESSION['user_id'],$nilai,$nilai);
            mysqli_stmt_execute($rs3); mysqli_stmt_close($rs3);
            header("Location: /resep-masakan/detail-resep.php?slug=".urlencode($slug)); exit;
        }
    }
}

$page_title = htmlspecialchars($resep['judul']);
require_once 'includes/header.php';

// Warna kesulitan
$diff_color = ['mudah'=>'#2d6a4f','sedang'=>'#d68910','sulit'=>'#c0392b'];
$dc = $diff_color[$resep['tingkat_kesulitan']] ?? '#2d6a4f';
?>

<!-- ===== HERO SECTION ===== -->
<div style="background:linear-gradient(160deg,#0a2e1a,#1a4731,#2d6a4f);position:relative;overflow:hidden;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 80% 50%,rgba(82,183,136,0.15) 0%,transparent 60%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;padding:2.5rem 1.5rem 5rem;">
        <!-- Breadcrumb -->
        <div style="font-size:0.75rem;color:rgba(255,255,255,0.45);margin-bottom:1.2rem;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <a href="/resep-masakan/home.php" style="color:#52b788;text-decoration:none;">Beranda</a>
            <i class="fas fa-chevron-right" style="font-size:0.55rem;"></i>
            <a href="/resep-masakan/resep.php" style="color:#52b788;text-decoration:none;">Resep</a>
            <i class="fas fa-chevron-right" style="font-size:0.55rem;"></i>
            <span style="color:rgba(255,255,255,0.6);"><?= htmlspecialchars(mb_strimwidth($resep['judul'],0,40,'...')) ?></span>
        </div>

        <!-- Judul & meta -->
        <div style="display:flex;gap:4rem;align-items:flex-start;flex-wrap:wrap;">
            <div style="flex:1;min-width:280px;">
                <!-- Kategori badges -->
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:1rem;">
                    <span style="background:<?= $dc ?>;color:#fff;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;">
                        <?= ucfirst($resep['tingkat_kesulitan']) ?>
                    </span>
                    <?php foreach ($kategori_resep as $kr): ?>
                    <a href="/resep-masakan/resep.php?kategori=<?= $kr['slug'] ?>" style="background:rgba(82,183,136,0.2);color:#95d5b2;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:600;text-decoration:none;border:1px solid rgba(82,183,136,0.3);">
                        <?= htmlspecialchars($kr['nama_kategori']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <h1 style="font-family:'Playfair Display',serif;font-size:clamp(1.8rem,4vw,2.5rem);color:#fff;font-weight:700;line-height:1.2;margin-bottom:1.2rem;letter-spacing:-0.5px;">
                    <?= htmlspecialchars($resep['judul']) ?>
                </h1>

                <!-- Penulis & info -->
                <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($resep['foto_user'] ?? 'default.png') ?>"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid rgba(82,183,136,0.5);"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <span style="font-size:0.85rem;color:rgba(255,255,255,0.7);font-weight:500;">
                            <?= htmlspecialchars($resep['nama_user']) ?>
                        </span>
                    </div>
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.45);">
                        <i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($resep['created_at'])) ?>
                    </span>
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.45);">
                        <i class="fas fa-eye"></i> <?= number_format($resep['views']) ?> dilihat
                    </span>
                    <div style="display:flex;align-items:center;gap:4px;">
                        <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.85rem;">
                            <?php for($s=1;$s<=5;$s++) echo $s<=round($resep['rata_rating'])?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:rgba(255,255,255,0.25);"></i>'; ?>
                        </div>
                        <span style="font-size:0.78rem;color:rgba(255,255,255,0.5);">(<?= $resep['jumlah_rating'] ?>)</span>
                    </div>
                </div>
            </div>

            <!-- Aksi -->
            <div style="display:flex;flex-direction:column;gap:8px;flex-shrink:0;">
                <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="toggleFavorit()" id="btn-favorit"
                        style="
                            display:inline-flex;align-items:center;gap:8px;
                            padding:10px 20px;border-radius:20px;font-size:0.875rem;font-weight:600;
                            border:none;cursor:pointer;transition:all 0.2s;font-family:inherit;
                            <?= $is_favorit
                                ? 'background:#e74c3c;color:#fff;'
                                : 'background:rgba(255,255,255,0.1);color:#fff;border:1.5px solid rgba(255,255,255,0.2);' ?>
                        ">
                    <i class="<?= $is_favorit ? 'fas' : 'far' ?> fa-heart"></i>
                    <?= $is_favorit ? 'Tersimpan' : 'Simpan' ?>
                </button>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id']==$resep['id_pemilik'] || $_SESSION['role']==='admin')): ?>
                <a href="/resep-masakan/user/edit-resep.php?id=<?= $resep['id'] ?>"
                   style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:20px;font-size:0.875rem;font-weight:600;background:rgba(255,255,255,0.1);color:#fff;text-decoration:none;border:1.5px solid rgba(255,255,255,0.2);transition:all 0.2s;"
                   onmouseover="this.style.background='rgba(255,255,255,0.18)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <i class="fas fa-edit"></i> Edit Resep
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Wave -->
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 50" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:50px;">
            <path d="M0,25 C360,50 1080,0 1440,25 L1440,50 L0,50 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div style="background:#f4f7f4;padding:0 0 5rem;">
<div class="container">
<div style="display:grid;grid-template-columns:1fr 320px;gap:2rem;align-items:start;margin-top:-30px;position:relative;z-index:2;">

    <!-- ===== KIRI: KONTEN ===== -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

        <!-- Foto utama -->
        <div style="border-radius:24px;overflow:hidden;box-shadow:0 8px 32px rgba(45,106,79,0.15);">
            <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                 alt="<?= htmlspecialchars($resep['judul']) ?>"
                 style="width:100%;height:400px;object-fit:cover;display:block;"
                 onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
        </div>

        <!-- Info cepat 4 kotak -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;">
            <?php
            $info_items = [
                ['icon'=>'fa-clock','val'=>$resep['waktu_masak'].' mnt','label'=>'Waktu Masak','bg'=>'rgba(45,106,79,0.06)','ic_color'=>'#2d6a4f'],
                ['icon'=>'fa-users','val'=>$resep['porsi'].' porsi','label'=>'Porsi','bg'=>'rgba(52,152,219,0.06)','ic_color'=>'#2471a3'],
                ['icon'=>'fa-signal','val'=>ucfirst($resep['tingkat_kesulitan']),'label'=>'Kesulitan','bg'=>'rgba('.($resep['tingkat_kesulitan']==='mudah'?'45,106,79':($resep['tingkat_kesulitan']==='sedang'?'214,137,16':'192,57,43')).',0.08)','ic_color'=>$dc],
                ['icon'=>'fa-star','val'=>number_format($resep['rata_rating'],1),'label'=>$resep['jumlah_rating'].' rating','bg'=>'rgba(245,158,11,0.06)','ic_color'=>'#d97706'],
            ];
            foreach ($info_items as $ii):
            ?>
            <div style="background:#fff;border-radius:16px;padding:1.2rem;text-align:center;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
                <div style="width:40px;height:40px;background:<?= $ii['bg'] ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.6rem;">
                    <i class="fas <?= $ii['icon'] ?>" style="color:<?= $ii['ic_color'] ?>;font-size:1rem;"></i>
                </div>
                <div style="font-size:1rem;font-weight:700;color:#1a2e1a;"><?= $ii['val'] ?></div>
                <div style="font-size:0.7rem;color:#8a9e87;margin-top:2px;"><?= $ii['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Deskripsi -->
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <p style="color:#4a6741;line-height:1.9;font-size:0.95rem;margin:0;"><?= nl2br(htmlspecialchars($resep['deskripsi'])) ?></p>
        </div>

        <!-- Nutrisi -->
        <?php if ($nutrisi && ($nutrisi['kalori'] || $nutrisi['protein'] || $nutrisi['karbohidrat'] || $nutrisi['lemak'] || $nutrisi['serat'])): ?>
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#1a2e1a;margin-bottom:1.4rem;display:flex;align-items:center;gap:10px;">
                <span style="width:32px;height:32px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-heartbeat" style="color:#2d6a4f;font-size:0.9rem;"></i>
                </span>
                Informasi Nutrisi <small style="font-family:'Poppins',sans-serif;font-size:0.72rem;color:#8a9e87;font-weight:400;">per porsi</small>
            </h2>
            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;">
                <?php
                $ni_list = [
                    ['label'=>'Kalori','value'=>$nutrisi['kalori'],'unit'=>'kkal','color'=>'#e74c3c','bg'=>'rgba(231,76,60,0.08)'],
                    ['label'=>'Protein','value'=>$nutrisi['protein'],'unit'=>'g','color'=>'#3498db','bg'=>'rgba(52,152,219,0.08)'],
                    ['label'=>'Karbo','value'=>$nutrisi['karbohidrat'],'unit'=>'g','color'=>'#f39c12','bg'=>'rgba(243,156,18,0.08)'],
                    ['label'=>'Lemak','value'=>$nutrisi['lemak'],'unit'=>'g','color'=>'#27ae60','bg'=>'rgba(39,174,96,0.08)'],
                    ['label'=>'Serat','value'=>$nutrisi['serat'],'unit'=>'g','color'=>'#8e44ad','bg'=>'rgba(142,68,173,0.08)'],
                ];
                foreach ($ni_list as $ni):
                ?>
                <div style="text-align:center;padding:1.2rem 0.5rem;background:<?= $ni['bg'] ?>;border-radius:14px;border-top:3px solid <?= $ni['color'] ?>;">
                    <div style="font-size:1.3rem;font-weight:700;color:#1a2e1a;"><?= number_format($ni['value'],1) ?></div>
                    <div style="font-size:0.68rem;color:#8a9e87;margin:2px 0;"><?= $ni['unit'] ?></div>
                    <div style="font-size:0.75rem;font-weight:600;color:<?= $ni['color'] ?>;"><?= $ni['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bahan dengan centang -->
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;">
                <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#1a2e1a;display:flex;align-items:center;gap:10px;margin:0;">
                    <span style="width:32px;height:32px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-list-ul" style="color:#2d6a4f;font-size:0.9rem;"></i>
                    </span>
                    Bahan-bahan
                </h2>
                <?php if (!empty($bahan_list)): ?>
                <button onclick="resetCentang()" style="display:flex;align-items:center;gap:5px;background:none;border:1.5px solid #e4ede6;border-radius:20px;padding:5px 12px;font-size:0.72rem;color:#8a9e87;cursor:pointer;transition:all 0.2s;font-family:inherit;"
                        onmouseover="this.style.borderColor='#2d6a4f';this.style.color='#2d6a4f'"
                        onmouseout="this.style.borderColor='#e4ede6';this.style.color='#8a9e87'">
                    <i class="fas fa-redo" style="font-size:0.65rem;"></i> Reset
                </button>
                <?php endif; ?>
            </div>
            <?php if (empty($bahan_list)): ?>
                <p style="color:#8a9e87;">Belum ada bahan.</p>
            <?php else: ?>
            <p style="font-size:0.78rem;color:#8a9e87;margin-bottom:1rem;display:flex;align-items:center;gap:5px;">
                <i class="fas fa-hand-pointer"></i> Klik bahan yang sudah kamu siapkan
            </p>
            <ul style="list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="bahan-checklist">
                <?php foreach ($bahan_list as $idx => $b): ?>
                <li id="bahan-item-<?= $idx ?>"
                    onclick="toggleBahan(<?= $idx ?>)"
                    style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f4f7f4;border-radius:12px;font-size:0.875rem;cursor:pointer;transition:all 0.25s;border:1.5px solid transparent;user-select:none;">
                    <div id="check-icon-<?= $idx ?>" style="width:20px;height:20px;border:2px solid #c8ddc8;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all 0.2s;">
                        <i class="fas fa-check" style="font-size:0.6rem;color:transparent;"></i>
                    </div>
                    <span id="bahan-text-<?= $idx ?>" style="color:#1a2e1a;">
                        <strong><?= htmlspecialchars($b['jumlah']) ?> <?= htmlspecialchars($b['satuan'] ?? '') ?></strong>
                        <?= htmlspecialchars($b['nama_bahan']) ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Alat Masak -->
        <?php if (!empty($alat_list)): ?>
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#1a2e1a;margin-bottom:1.2rem;display:flex;align-items:center;gap:10px;">
                <span style="width:32px;height:32px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-tools" style="color:#2d6a4f;font-size:0.9rem;"></i>
                </span>
                Alat Masak
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <?php foreach ($alat_list as $a): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#f4f7f4;border-radius:12px;font-size:0.875rem;">
                    <div style="width:28px;height:28px;background:rgba(45,106,79,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-wrench" style="color:#2d6a4f;font-size:0.75rem;"></i>
                    </div>
                    <?= htmlspecialchars($a['nama_alat']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Langkah -->
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#1a2e1a;margin-bottom:1.5rem;display:flex;align-items:center;gap:10px;">
                <span style="width:32px;height:32px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-list-ol" style="color:#2d6a4f;font-size:0.9rem;"></i>
                </span>
                Cara Memasak
            </h2>
            <?php if (empty($langkah_list)): ?>
                <p style="color:#8a9e87;">Belum ada langkah.</p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                <?php foreach ($langkah_list as $l): ?>
                <div style="display:flex;gap:1.2rem;align-items:flex-start;">
                    <div style="
                        width:36px;height:36px;flex-shrink:0;
                        background:linear-gradient(135deg,#2d6a4f,#40916c);
                        color:#fff;border-radius:12px;
                        display:flex;align-items:center;justify-content:center;
                        font-weight:700;font-size:0.9rem;
                        box-shadow:0 3px 10px rgba(45,106,79,0.3);
                    "><?= $l['urutan'] ?></div>
                    <div style="flex:1;padding-top:6px;">
                        <p style="color:#4a6741;line-height:1.9;font-size:0.95rem;margin:0;">
                            <?= nl2br(htmlspecialchars($l['instruksi'])) ?>
                        </p>
                        <?php if ($l['foto_langkah']): ?>
                        <img src="/resep-masakan/uploads/langkah/<?= htmlspecialchars($l['foto_langkah']) ?>"
                             style="margin-top:12px;width:100%;max-width:420px;border-radius:14px;object-fit:cover;border:1.5px solid #e4ede6;">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Komentar -->
        <div id="komentar" style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;color:#1a2e1a;margin-bottom:1.5rem;display:flex;align-items:center;gap:10px;">
                <span style="width:32px;height:32px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-comments" style="color:#2d6a4f;font-size:0.9rem;"></i>
                </span>
                Komentar
                <span style="background:#f4f7f4;color:#8a9e87;padding:2px 10px;border-radius:20px;font-size:0.78rem;font-weight:500;font-family:'Poppins',sans-serif;">
                    <?= count($komentar_list) ?>
                </span>
            </h2>

            <?php if ($pesan): ?>
                <?php [$type, $msg] = explode(':',$pesan,2); ?>
                <div style="padding:12px 16px;border-radius:10px;font-size:0.875rem;margin-bottom:1rem;display:flex;align-items:center;gap:8px;
                    background:<?= $type==='error'?'rgba(231,76,60,0.08)':'rgba(45,106,79,0.08)' ?>;
                    border:1px solid <?= $type==='error'?'rgba(231,76,60,0.2)':'rgba(45,106,79,0.2)' ?>;
                    color:<?= $type==='error'?'#c0392b':'#2d6a4f' ?>;">
                    <i class="fas fa-<?= $type==='error'?'exclamation-circle':'check-circle' ?>"></i>
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <!-- Form komentar -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" style="margin-bottom:2rem;background:#f4f7f4;border-radius:16px;padding:1.2rem;">
                <input type="hidden" name="parent_id" value="0">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($_SESSION['foto'] ?? 'default.png') ?>"
                         style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #c8ddc8;flex-shrink:0;"
                         onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                    <div style="flex:1;">
                        <textarea name="komentar" rows="3" placeholder="Bagikan pengalamanmu memasak resep ini..."
                                  style="width:100%;padding:10px 14px;background:#fff;border:1.5px solid #e4ede6;border-radius:12px;font-size:0.875rem;font-family:inherit;color:#1a2e1a;outline:none;resize:none;transition:border-color 0.2s;"
                                  onfocus="this.style.borderColor='#2d6a4f'"
                                  onblur="this.style.borderColor='#e4ede6'"></textarea>
                        <div style="display:flex;justify-content:flex-end;margin-top:8px;">
                            <button type="submit" style="display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;border:none;border-radius:20px;padding:9px 20px;font-size:0.82rem;font-weight:600;cursor:pointer;font-family:inherit;box-shadow:0 3px 10px rgba(45,106,79,0.3);">
                                <i class="fas fa-paper-plane"></i> Kirim Komentar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <?php else: ?>
            <div style="background:#f4f7f4;border-radius:14px;padding:1.2rem;margin-bottom:1.5rem;text-align:center;font-size:0.875rem;color:#8a9e87;">
                <a href="/resep-masakan/login.php" style="color:#2d6a4f;font-weight:600;">Login</a> untuk berkomentar
            </div>
            <?php endif; ?>

            <!-- List komentar -->
            <?php if (empty($komentar_list)): ?>
            <div style="text-align:center;padding:3rem;color:#8a9e87;">
                <i class="far fa-comment-dots" style="font-size:2.5rem;display:block;margin-bottom:0.8rem;opacity:0.3;"></i>
                Belum ada komentar. Jadilah yang pertama!
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1.2rem;">
                <?php foreach ($komentar_list as $k): ?>
                <div>
                    <!-- Komentar utama -->
                    <div style="display:flex;gap:12px;padding:1rem;background:#f4f7f4;border-radius:14px;">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($k['foto'] ?? 'default.png') ?>"
                             style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #c8ddc8;"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;flex-wrap:wrap;gap:6px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <strong style="font-size:0.875rem;color:#1a2e1a;"><?= htmlspecialchars($k['nama']) ?></strong>
                                    <span style="font-size:0.72rem;color:#8a9e87;"><?= date('d M Y H:i', strtotime($k['created_at'])) ?></span>
                                </div>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                <button onclick="toggleReplyForm('reply-form-<?= $k['id'] ?>')"
                                        style="display:flex;align-items:center;gap:5px;background:none;border:1.5px solid #c8ddc8;border-radius:20px;padding:3px 10px;font-size:0.75rem;color:#40916c;cursor:pointer;font-family:inherit;transition:all 0.2s;"
                                        onmouseover="this.style.background='rgba(64,145,108,0.08)'"
                                        onmouseout="this.style.background='none'">
                                    <i class="fas fa-reply"></i> Balas
                                    <?php if (!empty($k['replies'])): ?>
                                    <span style="background:#40916c;color:#fff;border-radius:20px;padding:0 6px;font-size:0.65rem;"><?= count($k['replies']) ?></span>
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.875rem;color:#4a6741;margin:0;line-height:1.7;">
                                <?= nl2br(htmlspecialchars($k['komentar'])) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Balasan -->
                    <?php if (!empty($k['replies'])): ?>
                    <div style="margin-left:50px;margin-top:8px;display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($k['replies'] as $reply): ?>
                        <div>
                            <div style="display:flex;gap:10px;padding:0.8rem;background:#eef7f0;border-radius:12px;border-left:3px solid #95d5b2;">
                                <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($reply['foto'] ?? 'default.png') ?>"
                                     style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #c8ddc8;"
                                     onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                                <div style="flex:1;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;flex-wrap:wrap;gap:5px;">
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <strong style="font-size:0.82rem;color:#1a2e1a;"><?= htmlspecialchars($reply['nama']) ?></strong>
                                            <span style="font-size:0.68rem;color:#8a9e87;"><?= date('d M Y H:i', strtotime($reply['created_at'])) ?></span>
                                            <span style="font-size:0.65rem;background:#d8f3dc;color:#2d6a4f;padding:1px 7px;border-radius:10px;font-weight:600;">balasan</span>
                                        </div>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                        <button onclick="toggleReplyForm('reply-form-r-<?= $reply['id'] ?>')"
                                                style="display:flex;align-items:center;gap:4px;background:none;border:1px solid #c8ddc8;border-radius:20px;padding:2px 8px;font-size:0.7rem;color:#40916c;cursor:pointer;font-family:inherit;">
                                            <i class="fas fa-reply"></i> Balas
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <p style="font-size:0.82rem;color:#4a6741;margin:0;line-height:1.6;"><?= nl2br(htmlspecialchars($reply['komentar'])) ?></p>
                                </div>
                            </div>
                            <!-- Form balas reply -->
                            <?php if (isset($_SESSION['user_id'])): ?>
                            <div id="reply-form-r-<?= $reply['id'] ?>" style="display:none;margin-left:40px;margin-top:6px;">
                                <form method="POST" style="background:#f4f7f4;border-radius:12px;padding:0.8rem;">
                                    <input type="hidden" name="parent_id" value="<?= $k['id'] ?>">
                                    <textarea name="komentar" rows="2" placeholder="Balas @<?= htmlspecialchars($reply['nama']) ?>..."
                                              style="width:100%;padding:8px 12px;background:#fff;border:1.5px solid #e4ede6;border-radius:10px;font-size:0.82rem;font-family:inherit;color:#1a2e1a;outline:none;resize:none;margin-bottom:6px;"
                                              onfocus="this.style.borderColor='#2d6a4f'"
                                              onblur="this.style.borderColor='#e4ede6'"></textarea>
                                    <div style="display:flex;gap:6px;">
                                        <button type="submit" style="display:inline-flex;align-items:center;gap:5px;background:#2d6a4f;color:#fff;border:none;border-radius:20px;padding:7px 14px;font-size:0.78rem;font-weight:600;cursor:pointer;font-family:inherit;">
                                            <i class="fas fa-paper-plane"></i> Kirim
                                        </button>
                                        <button type="button" onclick="toggleReplyForm('reply-form-r-<?= $reply['id'] ?>')"
                                                style="display:inline-flex;align-items:center;background:#f4f7f4;color:#8a9e87;border:1.5px solid #e4ede6;border-radius:20px;padding:7px 14px;font-size:0.78rem;cursor:pointer;font-family:inherit;">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Form balas komentar utama -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div id="reply-form-<?= $k['id'] ?>" style="display:none;margin-left:50px;margin-top:8px;">
                        <form method="POST" style="background:#f4f7f4;border-radius:12px;padding:0.8rem;">
                            <input type="hidden" name="parent_id" value="<?= $k['id'] ?>">
                            <textarea name="komentar" rows="2" placeholder="Balas <?= htmlspecialchars($k['nama']) ?>..."
                                      style="width:100%;padding:8px 12px;background:#fff;border:1.5px solid #e4ede6;border-radius:10px;font-size:0.875rem;font-family:inherit;color:#1a2e1a;outline:none;resize:none;margin-bottom:6px;"
                                      onfocus="this.style.borderColor='#2d6a4f'"
                                      onblur="this.style.borderColor='#e4ede6'"></textarea>
                            <div style="display:flex;gap:6px;">
                                <button type="submit" style="display:inline-flex;align-items:center;gap:5px;background:#2d6a4f;color:#fff;border:none;border-radius:20px;padding:7px 16px;font-size:0.82rem;font-weight:600;cursor:pointer;font-family:inherit;">
                                    <i class="fas fa-paper-plane"></i> Kirim Balasan
                                </button>
                                <button type="button" onclick="toggleReplyForm('reply-form-<?= $k['id'] ?>')"
                                        style="background:#f4f7f4;color:#8a9e87;border:1.5px solid #e4ede6;border-radius:20px;padding:7px 14px;font-size:0.82rem;cursor:pointer;font-family:inherit;border:1.5px solid #e4ede6;">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== KANAN: SIDEBAR ===== -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;position:sticky;top:90px;">

        <!-- Rating -->
        <div style="background:#fff;border-radius:20px;padding:1.5rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h3 style="font-size:0.9rem;font-weight:700;color:#1a2e1a;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-star" style="color:#f59e0b;"></i> Beri Rating
            </h3>
            <div style="text-align:center;margin-bottom:1.2rem;">
                <div style="font-size:3rem;font-weight:700;color:#1a2e1a;letter-spacing:-1px;line-height:1;">
                    <?= number_format($resep['rata_rating'],1) ?>
                </div>
                <div style="display:flex;justify-content:center;gap:4px;color:#f59e0b;font-size:1.1rem;margin:6px 0;">
                    <?php for($s=1;$s<=5;$s++) echo $s<=round($resep['rata_rating'])?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#e0e0e0;"></i>'; ?>
                </div>
                <div style="font-size:0.75rem;color:#8a9e87;"><?= $resep['jumlah_rating'] ?> penilaian</div>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST">
                <div style="display:flex;justify-content:center;gap:8px;margin-bottom:1rem;" id="star-input">
                    <?php for($s=1;$s<=5;$s++): ?>
                    <label style="cursor:pointer;font-size:1.6rem;color:<?= $s<=$rating_user?'#f59e0b':'#e0e0e0' ?>;transition:color 0.15s;"
                           onmouseover="highlightStars(<?= $s ?>)" onmouseout="resetStars()">
                        <input type="radio" name="rating" value="<?= $s ?>" style="display:none;" <?= $s==$rating_user?'checked':'' ?>>
                        <i class="<?= $s<=$rating_user?'fas':'far' ?> fa-star" id="star-<?= $s ?>"></i>
                    </label>
                    <?php endfor; ?>
                </div>
                <button type="submit" style="width:100%;padding:10px;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;border:none;border-radius:12px;font-size:0.875rem;font-weight:600;cursor:pointer;font-family:inherit;box-shadow:0 3px 10px rgba(45,106,79,0.25);">
                    <i class="fas fa-check"></i> <?= $rating_user ? 'Update Rating' : 'Beri Rating' ?>
                </button>
            </form>
            <?php else: ?>
            <p style="text-align:center;font-size:0.85rem;color:#8a9e87;">
                <a href="/resep-masakan/login.php" style="color:#2d6a4f;font-weight:600;">Login</a> untuk memberi rating
            </p>
            <?php endif; ?>
        </div>

        <!-- Info Resep -->
        <div style="background:#fff;border-radius:20px;padding:1.5rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h3 style="font-size:0.9rem;font-weight:700;color:#1a2e1a;margin-bottom:1rem;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-info-circle" style="color:#2d6a4f;"></i> Info Resep
            </h3>
            <div style="display:flex;flex-direction:column;gap:0;">
                <?php
                $info_rows = [
                    ['Kategori', implode(', ', array_column($kategori_resep,'nama_kategori'))],
                    ['Jenis', $resep['nama_jenis']],
                    ['Bahan', count($bahan_list).' item'],
                    ['Langkah', count($langkah_list).' langkah'],
                    ['Alat', empty($alat_list)?'-':count($alat_list).' alat'],
                ];
                foreach ($info_rows as $ri):
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f5f0;">
                    <span style="font-size:0.8rem;color:#8a9e87;"><?= $ri[0] ?></span>
                    <span style="font-size:0.82rem;font-weight:600;color:#1a2e1a;text-align:right;max-width:160px;"><?= htmlspecialchars($ri[1]) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Penulis -->
        <div style="background:linear-gradient(135deg,#0a2e1a,#1a4731);border-radius:20px;padding:1.5rem;box-shadow:0 4px 16px rgba(10,46,26,0.2);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem;">
                <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($resep['foto_user'] ?? 'default.png') ?>"
                     style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid rgba(82,183,136,0.5);"
                     onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                <div>
                    <div style="font-size:0.9rem;font-weight:700;color:#fff;"><?= htmlspecialchars($resep['nama_user']) ?></div>
                    <div style="font-size:0.72rem;color:rgba(255,255,255,0.45);">Penulis Resep</div>
                </div>
            </div>
            <a href="/resep-masakan/resep.php?q=<?= urlencode($resep['nama_user']) ?>"
               style="display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,0.1);color:#95d5b2;border:1px solid rgba(149,213,178,0.2);border-radius:20px;padding:8px;font-size:0.78rem;font-weight:600;text-decoration:none;transition:all 0.2s;"
               onmouseover="this.style.background='rgba(255,255,255,0.18)'"
               onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                Lihat Resep Lainnya <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

</div>
</div>
</div>

<script>
// ===== CENTANG BAHAN =====
const resepId    = '<?= $resep["id"] ?>';
const storageKey = 'bahan_checked_' + resepId;

function loadCentang() {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
    saved.forEach(idx => applyChecked(idx, true));
}

function toggleBahan(idx) {
    const item = document.getElementById('bahan-item-' + idx);
    applyChecked(idx, !item.classList.contains('checked'));
    saveCentang();
}

function applyChecked(idx, checked) {
    const item = document.getElementById('bahan-item-' + idx);
    const icon = document.getElementById('check-icon-' + idx);
    const text = document.getElementById('bahan-text-' + idx);
    if (!item) return;
    if (checked) {
        item.classList.add('checked');
        item.style.background   = 'rgba(45,106,79,0.08)';
        item.style.borderColor  = '#95d5b2';
        icon.style.background   = '#2d6a4f';
        icon.style.borderColor  = '#2d6a4f';
        icon.querySelector('i').style.color = '#fff';
        text.style.textDecoration = 'line-through';
        text.style.color          = '#8a9e87';
    } else {
        item.classList.remove('checked');
        item.style.background   = '#f4f7f4';
        item.style.borderColor  = 'transparent';
        icon.style.background   = 'transparent';
        icon.style.borderColor  = '#c8ddc8';
        icon.querySelector('i').style.color = 'transparent';
        text.style.textDecoration = 'none';
        text.style.color          = '#1a2e1a';
    }
}

function saveCentang() {
    const checked = [];
    document.querySelectorAll('#bahan-checklist li.checked').forEach(li => {
        checked.push(li.id.replace('bahan-item-', ''));
    });
    localStorage.setItem(storageKey, JSON.stringify(checked));
}

function resetCentang() {
    localStorage.removeItem(storageKey);
    document.querySelectorAll('#bahan-checklist li').forEach((li, idx) => applyChecked(idx, false));
}

loadCentang();

// ===== REPLY FORM =====
function toggleReplyForm(id) {
    const form = document.getElementById(id);
    if (!form) return;
    const isHidden = form.style.display === 'none' || form.style.display === '';
    document.querySelectorAll('[id^="reply-form-"]').forEach(f => f.style.display = 'none');
    if (isHidden) {
        form.style.display = 'block';
        const ta = form.querySelector('textarea');
        if (ta) ta.focus();
    }
}

// ===== FAVORIT =====
function toggleFavorit() {
    fetch('/resep-masakan/user/ajax-favorit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_resep=<?= $resep["id"] ?>'
    })
    .then(r => r.json())
    .then(data => {
        const btn = document.getElementById('btn-favorit');
        if (data.status === 'added') {
            btn.innerHTML = '<i class="fas fa-heart"></i> Tersimpan';
            btn.style.background = '#e74c3c';
            btn.style.border = 'none';
        } else {
            btn.innerHTML = '<i class="far fa-heart"></i> Simpan';
            btn.style.background = 'rgba(255,255,255,0.1)';
            btn.style.border = '1.5px solid rgba(255,255,255,0.2)';
        }
    });
}

// ===== RATING HOVER =====
let selectedRating = <?= $rating_user ?>;
function highlightStars(n) {
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (!star) continue;
        star.className = i <= n ? 'fas fa-star' : 'far fa-star';
        star.parentElement.style.color = i <= n ? '#f59e0b' : '#e0e0e0';
    }
}
function resetStars() { highlightStars(selectedRating); }
document.querySelectorAll('#star-input input').forEach(input => {
    input.addEventListener('change', () => { selectedRating = parseInt(input.value); });
});
</script>

<?php require_once 'includes/footer.php'; ?>
