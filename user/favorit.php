<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) { header('Location: /resep-masakan/login.php'); exit; }

$page_title = 'Resep Favorit';
$user_id    = (int)$_SESSION['user_id'];
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 9;
$offset     = ($page - 1) * $per_page;

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM favorit WHERE id_user=$user_id"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating,
     f.created_at as tanggal_simpan
     FROM favorit f
     JOIN resep r ON f.id_resep=r.id
     LEFT JOIN users u ON r.user_id=u.id
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE f.id_user=$user_id
     GROUP BY r.id, f.created_at
     ORDER BY f.created_at DESC
     LIMIT $per_page OFFSET $offset");
$favorit_list = [];
while ($row = mysqli_fetch_assoc($result)) $favorit_list[] = $row;

require_once '../includes/header.php';
?>

<!-- HERO -->
<div style="background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);padding:3rem 0 5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <p style="font-size:0.72rem;font-weight:600;color:#52b788;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">Koleksi Saya</p>
        <h1 style="font-family:'Playfair Display',serif;font-size:2rem;color:#fff;font-weight:700;letter-spacing:-0.5px;display:flex;align-items:center;gap:12px;">
            <i class="fas fa-heart" style="color:#ff6b6b;font-size:1.6rem;"></i>
            Resep <span style="color:#95d5b2;">Favorit</span>
        </h1>
        <p style="color:rgba(255,255,255,0.5);font-size:0.875rem;margin-top:4px;"><?= $total ?> resep tersimpan</p>
    </div>
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:40px;">
            <path d="M0,20 C360,40 1080,0 1440,20 L1440,40 L0,40 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<div style="background:#f4f7f4;padding:2rem 0 5rem;">
<div class="container">

    <?php if (empty($favorit_list)): ?>
    <div style="background:#fff;border-radius:20px;border:1.5px solid #e4ede6;padding:6rem 2rem;text-align:center;">
        <div style="width:80px;height:80px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
            <i class="fas fa-heart" style="font-size:2rem;color:#ffb3b3;"></i>
        </div>
        <h3 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:#1a2e1a;margin-bottom:0.5rem;">Belum ada favorit</h3>
        <p style="color:#8a9e87;margin-bottom:2rem;font-size:0.875rem;max-width:360px;margin-left:auto;margin-right:auto;">Simpan resep yang kamu suka dengan menekan ikon hati di halaman resep</p>
        <a href="/resep-masakan/resep.php" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;padding:11px 24px;border-radius:20px;font-size:0.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 14px rgba(45,106,79,0.3);">
            <i class="fas fa-search"></i> Jelajahi Resep
        </a>
    </div>

    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem;margin-bottom:2rem;">
        <?php foreach ($favorit_list as $resep): ?>
        <div style="background:#fff;border-radius:20px;overflow:hidden;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);display:flex;flex-direction:column;transition:all 0.3s;"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 14px 36px rgba(45,106,79,0.12)';this.style.borderColor='#95d5b2'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.05)';this.style.borderColor='#e4ede6'">
            <div style="position:relative;overflow:hidden;height:185px;flex-shrink:0;cursor:pointer;"
                 onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <!-- Hapus favorit -->
                <button onclick="event.stopPropagation(); hapusFavorit(<?= $resep['id'] ?>, this)"
                        style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,0.9);border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#e74c3c;font-size:0.85rem;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:all 0.2s;"
                        onmouseover="this.style.background='#e74c3c';this.style.color='#fff'"
                        onmouseout="this.style.background='rgba(255,255,255,0.9)';this.style.color='#e74c3c'">
                    <i class="fas fa-heart"></i>
                </button>
                <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.6);color:#fff;padding:3px 8px;border-radius:20px;font-size:0.65rem;">
                    <i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt
                </span>
            </div>
            <div style="padding:1.1rem;flex:1;display:flex;flex-direction:column;cursor:pointer;"
                 onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'">
                <div style="font-size:0.68rem;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">
                    <?= htmlspecialchars($resep['nama_kategori']) ?>
                </div>
                <h3 style="font-family:'Playfair Display',serif;font-size:0.95rem;font-weight:700;color:#1a2e1a;margin-bottom:0.5rem;line-height:1.4;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= htmlspecialchars($resep['judul']) ?>
                </h3>
                <div style="display:flex;align-items:center;justify-content:space-between;padding-top:0.7rem;border-top:1px solid #f0f5f0;">
                    <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.72rem;">
                        <?php $rat=round($resep['rata_rating']); for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#e0e0e0;"></i>'; ?>
                        <span style="color:#8a9e87;margin-left:3px;">(<?= $resep['jumlah_rating'] ?>)</span>
                    </div>
                    <span style="font-size:0.68rem;color:#8a9e87;">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars(mb_strimwidth($resep['nama_user'],0,12,'...')) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_page > 1): ?>
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
        <?php if ($page>1): ?>
        <a href="?page=<?= $page-1 ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;"><i class="fas fa-chevron-left" style="font-size:0.72rem;"></i></a>
        <?php endif; ?>
        <?php for($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++): ?>
        <a href="?page=<?= $p ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;font-size:0.875rem;font-weight:600;text-decoration:none;
            <?= $p===$page?'background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;':'background:#fff;border:1.5px solid #e4ede6;color:#4a6741;' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page<$total_page): ?>
        <a href="?page=<?= $page+1 ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:0.72rem;"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</div>

<script>
function hapusFavorit(id, btn) {
    if (!confirm('Hapus dari favorit?')) return;
    fetch('/resep-masakan/user/ajax-favorit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_resep=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'removed') {
            const card = btn.closest('[onmouseover]');
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            card.style.transition = 'all 0.3s ease';
            setTimeout(() => card.remove(), 300);
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
