<?php
session_start();
require_once 'config/koneksi.php';
$page_title = 'Resep Populer';

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep WHERE status='publik'"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id=u.id
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE r.status='publik'
     GROUP BY r.id
     ORDER BY r.views DESC
     LIMIT $per_page OFFSET $offset");

$resep_list = [];
while ($row = mysqli_fetch_assoc($result)) $resep_list[] = $row;

require_once 'includes/header.php';
?>

<!-- HERO -->
<div style="background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);padding:3rem 0 5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-100px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <p style="font-size:0.72rem;font-weight:600;color:#52b788;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">
            <a href="/resep-masakan/home.php" style="color:#52b788;text-decoration:none;">Beranda</a>
            <i class="fas fa-chevron-right" style="font-size:0.55rem;margin:0 6px;"></i> Populer
        </p>
        <h1 style="font-family:'Playfair Display',serif;font-size:2.5rem;color:#fff;font-weight:700;letter-spacing:-0.5px;margin-bottom:0.5rem;">
            Resep <span style="color:#95d5b2;">Terpopuler</span>
        </h1>
        <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Resep yang paling banyak dilihat dan dicintai oleh komunitas</p>
    </div>
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:40px;">
            <path d="M0,20 C360,40 1080,0 1440,20 L1440,40 L0,40 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<div style="background:#f4f7f4;padding:2rem 0 5rem;">
<div class="container">

    <!-- TOP 3 PODIUM -->
    <?php if (count($resep_list) >= 3): ?>
    <div style="margin-bottom:3rem;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-trophy" style="color:#fff;font-size:0.9rem;"></i>
            </div>
            <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:#1a2e1a;">Top 3 Terpopuler</h2>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1.15fr 1fr;gap:1.2rem;align-items:end;">
            <?php
            $podium = [
                ['idx'=>1,'h'=>'210px','rank'=>2,'medal'=>'linear-gradient(135deg,#9ca3af,#6b7280)','label'=>'Perak'],
                ['idx'=>0,'h'=>'260px','rank'=>1,'medal'=>'linear-gradient(135deg,#f59e0b,#d97706)','label'=>'Emas'],
                ['idx'=>2,'h'=>'185px','rank'=>3,'medal'=>'linear-gradient(135deg,#cd7f32,#a0522d)','label'=>'Perunggu'],
            ];
            foreach ($podium as $p):
                $r = $resep_list[$p['idx']];
            ?>
            <div onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($r['slug']) ?>'"
                 style="background:#fff;border-radius:20px;overflow:hidden;border:1.5px solid #e4ede6;cursor:pointer;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:0 2px 12px rgba(45,106,79,0.07);"
                 onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 20px 50px rgba(45,106,79,0.15)';this.style.borderColor='#95d5b2'"
                 onmouseout="this.style.transform='';this.style.boxShadow='0 2px 12px rgba(45,106,79,0.07)';this.style.borderColor='#e4ede6'">
                <!-- Gambar -->
                <div style="position:relative;overflow:hidden;height:<?= $p['h'] ?>;">
                    <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($r['thumbnail']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                         onmouseover="this.style.transform='scale(1.05)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                    <!-- Medal badge -->
                    <div style="position:absolute;top:12px;left:12px;width:36px;height:36px;background:<?= $p['medal'] ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;color:#fff;box-shadow:0 3px 10px rgba(0,0,0,0.3);">
                        #<?= $p['rank'] ?>
                    </div>
                    <!-- Views overlay -->
                    <div style="position:absolute;bottom:12px;right:12px;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);color:#fff;padding:4px 10px;border-radius:20px;font-size:0.68rem;">
                        <i class="fas fa-eye"></i> <?= number_format($r['views']) ?>
                    </div>
                </div>
                <!-- Info -->
                <div style="padding:1.2rem;">
                    <div style="font-size:0.68rem;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">
                        <?= htmlspecialchars($r['nama_kategori']) ?>
                    </div>
                    <h3 style="font-family:'Playfair Display',serif;font-size:0.95rem;font-weight:700;color:#1a2e1a;margin-bottom:0.8rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($r['judul']) ?>
                    </h3>
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.78rem;">
                            <?php $rat=round($r['rata_rating']); for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#ddd;"></i>'; ?>
                            <span style="color:#8a9e87;font-size:0.7rem;margin-left:4px;">(<?= $r['jumlah_rating'] ?>)</span>
                        </div>
                        <span style="font-size:0.72rem;color:#8a9e87;display:flex;align-items:center;gap:4px;">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars(mb_strimwidth($r['nama_user'],0,12,'...')) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- RESEP LAINNYA -->
    <?php if (count($resep_list) > 3): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-list" style="color:#2d6a4f;font-size:0.9rem;"></i>
            </div>
            <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:#1a2e1a;">Resep Lainnya</h2>
        </div>
        <span style="font-size:0.78rem;color:#8a9e87;"><?= $total ?> total resep</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;">
        <?php foreach (array_slice($resep_list, 3) as $i => $resep): ?>
        <div onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'"
             style="background:#fff;border-radius:18px;overflow:hidden;border:1.5px solid #e4ede6;cursor:pointer;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:0 2px 8px rgba(45,106,79,0.05);display:flex;flex-direction:column;"
             onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(45,106,79,0.13)';this.style.borderColor='#95d5b2'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.05)';this.style.borderColor='#e4ede6'">
            <div style="position:relative;overflow:hidden;height:160px;flex-shrink:0;">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <div style="position:absolute;top:10px;left:10px;width:28px;height:28px;background:rgba(10,46,26,0.75);backdrop-filter:blur(8px);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.72rem;color:#fff;">
                    #<?= $i+4 ?>
                </div>
                <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,0.6);color:#fff;padding:3px 8px;border-radius:12px;font-size:0.65rem;">
                    <i class="fas fa-eye"></i> <?= number_format($resep['views']) ?>
                </div>
            </div>
            <div style="padding:1rem;flex:1;display:flex;flex-direction:column;">
                <div style="font-size:0.65rem;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">
                    <?= htmlspecialchars($resep['nama_kategori']) ?>
                </div>
                <h3 style="font-family:'Playfair Display',serif;font-size:0.875rem;font-weight:700;color:#1a2e1a;line-height:1.4;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:8px;">
                    <?= htmlspecialchars($resep['judul']) ?>
                </h3>
                <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.72rem;">
                    <?php $rat=round($resep['rata_rating']); for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#e0e0e0;"></i>'; ?>
                    <span style="color:#8a9e87;margin-left:3px;">(<?= $resep['jumlah_rating'] ?>)</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- PAGINATION -->
    <?php if ($total_page > 1): ?>
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:2.5rem;">
        <?php if ($page>1): ?>
        <a href="?page=<?= $page-1 ?>" style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;transition:all 0.2s;"
           onmouseover="this.style.borderColor='#2d6a4f';this.style.color='#2d6a4f'"
           onmouseout="this.style.borderColor='#e4ede6';this.style.color='#4a6741'">
            <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
        </a>
        <?php endif; ?>
        <?php for($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++): ?>
        <a href="?page=<?= $p ?>" style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;text-decoration:none;font-size:0.875rem;font-weight:600;transition:all 0.2s;
            <?= $p===$page?'background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;border:1.5px solid transparent;':'background:#fff;border:1.5px solid #e4ede6;color:#4a6741;' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page<$total_page): ?>
        <a href="?page=<?= $page+1 ?>" style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;transition:all 0.2s;"
           onmouseover="this.style.borderColor='#2d6a4f';this.style.color='#2d6a4f'"
           onmouseout="this.style.borderColor='#e4ede6';this.style.color='#4a6741'">
            <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
