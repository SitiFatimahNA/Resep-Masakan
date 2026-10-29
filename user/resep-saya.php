<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) { header('Location: /resep-masakan/login.php'); exit; }

$page_title = 'Resep Saya';
$user_id    = (int)$_SESSION['user_id'];
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 9;
$offset     = ($page - 1) * $per_page;
$filter     = $_GET['filter'] ?? 'semua';

$where = "user_id = $user_id";
if ($filter === 'publik') $where .= " AND status='publik'";
if ($filter === 'draft')  $where .= " AND status='draft'";

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep WHERE $where"))['t'];
$total_page = ceil($total / $per_page);

$result = mysqli_query($koneksi,
    "SELECT r.*, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating, COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE $where GROUP BY r.id ORDER BY r.created_at DESC
     LIMIT $per_page OFFSET $offset");
$resep_list = [];
while ($row = mysqli_fetch_assoc($result)) $resep_list[] = $row;

$stats = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as total,
     COUNT(CASE WHEN status='publik' THEN 1 END) as publik,
     COUNT(CASE WHEN status='draft' THEN 1 END) as draft,
     COALESCE(SUM(views),0) as total_views
     FROM resep WHERE user_id=$user_id"));

require_once '../includes/header.php';
?>

<!-- HERO -->
<div style="background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);padding:3rem 0 5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <p style="font-size:0.72rem;font-weight:600;color:#52b788;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">Dapur Saya</p>
                <h1 style="font-family:'Playfair Display',serif;font-size:2rem;color:#fff;font-weight:700;letter-spacing:-0.5px;">
                    Resep <span style="color:#95d5b2;">Saya</span>
                </h1>
                <p style="color:rgba(255,255,255,0.5);font-size:0.875rem;margin-top:4px;">Kelola semua resep yang sudah kamu buat</p>
            </div>
            <a href="/resep-masakan/user/tambah-resep.php" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#52b788,#40916c);color:#fff;padding:12px 24px;border-radius:50px;font-size:0.875rem;font-weight:600;text-decoration:none;box-shadow:0 6px 20px rgba(82,183,136,0.4);transition:all 0.3s;"
               onmouseover="this.style.transform='translateY(-2px)'"
               onmouseout="this.style.transform=''">
                <i class="fas fa-plus"></i> Tambah Resep Baru
            </a>
        </div>
    </div>
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:40px;">
            <path d="M0,20 C360,40 1080,0 1440,20 L1440,40 L0,40 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<div style="background:#f4f7f4;padding:2rem 0 5rem;">
<div class="container">

    <?php if (isset($_GET['deleted'])): ?>
    <div style="display:flex;align-items:center;gap:8px;background:rgba(45,106,79,0.08);border:1.5px solid rgba(45,106,79,0.2);border-radius:12px;padding:12px 16px;font-size:0.875rem;color:#2d6a4f;margin-bottom:1.5rem;">
        <i class="fas fa-check-circle"></i> Resep berhasil dihapus.
    </div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;margin-bottom:2rem;">
        <?php
        $stat_items = [
            ['label'=>'Total Resep', 'value'=>$stats['total'],                'icon'=>'fa-book-open','bg'=>'rgba(45,106,79,0.1)','color'=>'#2d6a4f'],
            ['label'=>'Publik',      'value'=>$stats['publik'],               'icon'=>'fa-globe',    'bg'=>'rgba(52,152,219,0.1)','color'=>'#2471a3'],
            ['label'=>'Draft',       'value'=>$stats['draft'],                'icon'=>'fa-file-alt', 'bg'=>'rgba(243,156,18,0.1)','color'=>'#b7770d'],
            ['label'=>'Total Views', 'value'=>number_format($stats['total_views']),'icon'=>'fa-eye', 'bg'=>'rgba(155,89,182,0.1)','color'=>'#7d3c98'],
        ];
        foreach ($stat_items as $s):
        ?>
        <div style="background:#fff;border-radius:18px;padding:1.3rem;display:flex;align-items:center;gap:1rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);transition:all 0.3s;"
             onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(45,106,79,0.1)'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.05)'">
            <div style="width:48px;height:48px;background:<?= $s['bg'] ?>;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;font-size:1.1rem;"></i>
            </div>
            <div>
                <div style="font-size:1.6rem;font-weight:700;color:#1a2e1a;letter-spacing:-0.5px;line-height:1;"><?= $s['value'] ?></div>
                <div style="font-size:0.75rem;color:#8a9e87;margin-top:3px;font-weight:500;"><?= $s['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- FILTER TABS -->
    <div style="display:flex;gap:8px;margin-bottom:1.8rem;background:#fff;padding:6px;border-radius:14px;border:1.5px solid #e4ede6;width:fit-content;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
        <?php foreach (['semua'=>'Semua','publik'=>'Publik','draft'=>'Draft'] as $key=>$label): ?>
        <a href="?filter=<?= $key ?>" style="
            display:inline-flex;align-items:center;gap:6px;
            padding:8px 18px;border-radius:10px;font-size:0.82rem;font-weight:600;
            text-decoration:none;transition:all 0.2s;
            <?= $filter===$key
                ? 'background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;box-shadow:0 3px 10px rgba(45,106,79,0.25);'
                : 'color:#8a9e87;' ?>
        "><?= $label ?>
        <?php if ($key!=='semua'): ?>
        <span style="background:<?= $filter===$key?'rgba(255,255,255,0.25)':'#f4f7f4' ?>;border-radius:20px;padding:1px 7px;font-size:0.68rem;">
            <?= $key==='publik'?$stats['publik']:$stats['draft'] ?>
        </span>
        <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- GRID RESEP -->
    <?php if (empty($resep_list)): ?>
    <div style="background:#fff;border-radius:20px;border:1.5px solid #e4ede6;padding:5rem 2rem;text-align:center;">
        <div style="width:72px;height:72px;background:#f0faf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
            <i class="fas fa-utensils" style="font-size:1.8rem;color:#95d5b2;"></i>
        </div>
        <h3 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:#1a2e1a;margin-bottom:0.5rem;">Belum ada resep</h3>
        <p style="color:#8a9e87;margin-bottom:1.8rem;font-size:0.875rem;">Yuk bagikan resep masakan favoritmu!</p>
        <a href="/resep-masakan/user/tambah-resep.php" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;padding:11px 24px;border-radius:20px;font-size:0.875rem;font-weight:600;text-decoration:none;box-shadow:0 4px 14px rgba(45,106,79,0.3);">
            <i class="fas fa-plus"></i> Buat Resep Pertama
        </a>
    </div>

    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem;margin-bottom:2rem;">
        <?php foreach ($resep_list as $resep): ?>
        <div style="background:#fff;border-radius:20px;overflow:hidden;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);display:flex;flex-direction:column;transition:all 0.3s;"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 14px 36px rgba(45,106,79,0.12)';this.style.borderColor='#95d5b2'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.05)';this.style.borderColor='#e4ede6'">
            <!-- Gambar -->
            <div style="position:relative;overflow:hidden;height:180px;flex-shrink:0;cursor:pointer;"
                 onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'">
                <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                     style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                <!-- Status badge -->
                <span style="position:absolute;top:12px;left:12px;padding:4px 12px;border-radius:20px;font-size:0.68rem;font-weight:700;
                    background:<?= $resep['status']==='publik'?'#2d6a4f':'rgba(0,0,0,0.65)' ?>;color:#fff;">
                    <?= ucfirst($resep['status']) ?>
                </span>
                <!-- Views -->
                <span style="position:absolute;bottom:12px;right:12px;background:rgba(0,0,0,0.6);color:#fff;padding:3px 8px;border-radius:20px;font-size:0.65rem;">
                    <i class="fas fa-eye"></i> <?= number_format($resep['views']) ?>
                </span>
            </div>
            <!-- Konten -->
            <div style="padding:1.1rem;flex:1;display:flex;flex-direction:column;">
                <div style="font-size:0.68rem;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">
                    <?= htmlspecialchars($resep['nama_kategori']) ?>
                </div>
                <h3 style="font-family:'Playfair Display',serif;font-size:0.95rem;font-weight:700;color:#1a2e1a;margin-bottom:0.6rem;line-height:1.4;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= htmlspecialchars($resep['judul']) ?>
                </h3>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:0.8rem;">
                    <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.72rem;">
                        <?php $rat=round($resep['rata_rating']); for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#e0e0e0;"></i>'; ?>
                    </div>
                    <span style="font-size:0.68rem;color:#8a9e87;">(<?= $resep['jumlah_rating'] ?>)</span>
                    <span style="font-size:0.68rem;color:#8a9e87;margin-left:auto;">
                        <i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt
                    </span>
                </div>
                <!-- Actions -->
                <div style="display:flex;gap:6px;padding-top:0.8rem;border-top:1px solid #f0f5f0;">
                    <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>"
                       style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:#f4f7f4;border-radius:10px;font-size:0.75rem;font-weight:600;color:#4a6741;text-decoration:none;transition:all 0.2s;"
                       onmouseover="this.style.background='#e8f5e8'"
                       onmouseout="this.style.background='#f4f7f4'">
                        <i class="fas fa-eye"></i> Lihat
                    </a>
                    <a href="/resep-masakan/user/edit-resep.php?id=<?= $resep['id'] ?>"
                       style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:#f4f7f4;border-radius:10px;font-size:0.75rem;font-weight:600;color:#2d6a4f;text-decoration:none;transition:all 0.2s;"
                       onmouseover="this.style.background='#d8f3dc'"
                       onmouseout="this.style.background='#f4f7f4'">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="/resep-masakan/user/hapus-resep.php?id=<?= $resep['id'] ?>"
                       onclick="return confirm('Yakin ingin menghapus resep ini?')"
                       style="display:flex;align-items:center;justify-content:center;padding:8px 12px;background:#fef2f2;border-radius:10px;font-size:0.75rem;color:#c0392b;text-decoration:none;transition:all 0.2s;"
                       onmouseover="this.style.background='#fde8e8'"
                       onmouseout="this.style.background='#fef2f2'">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($total_page > 1): ?>
    <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
        <?php if ($page>1): ?>
        <a href="?filter=<?= $filter ?>&page=<?= $page-1 ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;"><i class="fas fa-chevron-left" style="font-size:0.72rem;"></i></a>
        <?php endif; ?>
        <?php for($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++): ?>
        <a href="?filter=<?= $filter ?>&page=<?= $p ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;font-size:0.875rem;font-weight:600;text-decoration:none;transition:all 0.2s;
            <?= $p===$page?'background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;':'background:#fff;border:1.5px solid #e4ede6;color:#4a6741;' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page<$total_page): ?>
        <a href="?filter=<?= $filter ?>&page=<?= $page+1 ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#fff;border:1.5px solid #e4ede6;color:#4a6741;text-decoration:none;"><i class="fas fa-chevron-right" style="font-size:0.72rem;"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
</div>

<?php require_once '../includes/footer.php'; ?>
