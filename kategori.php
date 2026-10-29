<?php
session_start();
require_once 'config/koneksi.php';
$page_title = 'Kategori';

$kategori_list = mysqli_query($koneksi,
    "SELECT k.*, COUNT(r.id) as total_resep, SUM(r.views) as total_views
     FROM kategori k
     LEFT JOIN resep r ON k.id=r.id_kategori AND r.status='publik'
     GROUP BY k.id ORDER BY total_resep DESC");

$jenis_list = mysqli_query($koneksi,
    "SELECT j.*, COUNT(r.id) as total_resep
     FROM jenis_masakan j
     LEFT JOIN resep r ON j.id=r.id_jenis AND r.status='publik'
     GROUP BY j.id ORDER BY total_resep DESC");

require_once 'includes/header.php';

$kat_img = [
    'ayam'=>'kat-ayam.jpg','daging-sapi'=>'kat-daging.jpg',
    'ikan'=>'kat-ikan.jpg','seafood'=>'kat-seafood.jpg',
    'sayuran'=>'kat-sayuran.jpg','tahu-tempe'=>'kat-tahu.jpg',
    'telur'=>'kat-telur.jpg','kambing'=>'kat-kambing.jpg',
    'bebek'=>'kat-bebek.jpg','buah-buahan'=>'kat-buah.jpg',
    'mie-pasta'=>'kat-mie.jpg','nasi'=>'kat-nasi.jpg',
];
$jenis_img = [
    'sarapan'=>'jenis-sarapan.jpg','makan-siang'=>'jenis-makan-siang.jpg',
    'makan-malam'=>'jenis-makan-malam.jpg','camilan'=>'jenis-camilan.jpg',
    'minuman'=>'jenis-minuman.jpg','dessert'=>'jenis-dessert.jpg',
    'sup-soto'=>'jenis-sup-soto.jpg','bakar-panggang'=>'jenis-bakar-panggang.jpg',
    'goreng'=>'jenis-goreng.jpg','rebus-kukus'=>'jenis-rebus-kukus.jpg',
];
?>

<!-- HERO -->
<div style="background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);padding:3rem 0 5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;bottom:-50px;right:-100px;width:350px;height:350px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <p style="font-size:0.72rem;font-weight:600;color:#52b788;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">
            <a href="/resep-masakan/home.php" style="color:#52b788;text-decoration:none;">Beranda</a>
            <i class="fas fa-chevron-right" style="font-size:0.55rem;margin:0 6px;"></i> Kategori
        </p>
        <h1 style="font-family:'Playfair Display',serif;font-size:2.5rem;color:#fff;font-weight:700;letter-spacing:-0.5px;margin-bottom:0.5rem;">
            Semua <span style="color:#95d5b2;">Kategori</span>
        </h1>
        <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Temukan resep berdasarkan bahan atau jenis sajian favoritmu</p>
    </div>
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:40px;">
            <path d="M0,20 C360,40 1080,0 1440,20 L1440,40 L0,40 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<div style="background:#f4f7f4;padding:2rem 0 5rem;">
<div class="container">

    <!-- KATEGORI BAHAN -->
    <div style="margin-bottom:4rem;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.8rem;">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,#2d6a4f,#40916c);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(45,106,79,0.3);">
                <i class="fas fa-tags" style="color:#fff;font-size:0.95rem;"></i>
            </div>
            <div>
                <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:#1a2e1a;letter-spacing:-0.3px;">Kategori Bahan</h2>
                <p style="font-size:0.78rem;color:#8a9e87;margin-top:2px;">Cari resep berdasarkan bahan utama</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;">
            <?php while ($k = mysqli_fetch_assoc($kategori_list)):
                $img = $kat_img[$k['slug']] ?? null;
            ?>
            <a href="/resep-masakan/resep.php?kategori=<?= urlencode($k['slug']) ?>"
               style="text-decoration:none;background:#fff;border-radius:20px;overflow:hidden;border:1.5px solid #e4ede6;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:0 2px 8px rgba(45,106,79,0.05);display:block;"
               onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 18px 45px rgba(45,106,79,0.14)';this.style.borderColor='#95d5b2'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.05)';this.style.borderColor='#e4ede6'">
                <div style="position:relative;height:130px;overflow:hidden;">
                    <?php if ($img): ?>
                    <img src="/resep-masakan/assets/images/kategori/<?= $img ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                         onmouseover="this.style.transform='scale(1.08)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.parentElement.style.background='#e8f5e8'">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,#d8f3dc,#95d5b2);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-utensils" style="font-size:2.5rem;color:#2d6a4f;opacity:0.4;"></i>
                    </div>
                    <?php endif; ?>
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(10,46,26,0.75) 0%,transparent 55%);"></div>
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:0.8rem;">
                        <div style="color:#fff;font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($k['nama_kategori']) ?></div>
                        <div style="color:rgba(255,255,255,0.65);font-size:0.7rem;margin-top:2px;"><?= $k['total_resep'] ?> resep</div>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- JENIS MASAKAN -->
    <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.8rem;">
            <div style="width:40px;height:40px;background:linear-gradient(135deg,#2d6a4f,#40916c);border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(45,106,79,0.3);">
                <i class="fas fa-utensils" style="color:#fff;font-size:0.95rem;"></i>
            </div>
            <div>
                <h2 style="font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;color:#1a2e1a;letter-spacing:-0.3px;">Jenis Masakan</h2>
                <p style="font-size:0.78rem;color:#8a9e87;margin-top:2px;">Cari berdasarkan waktu makan atau jenis sajian</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;">
            <?php while ($j = mysqli_fetch_assoc($jenis_list)):
                $jimg = $jenis_img[$j['slug']] ?? null;
            ?>
            <a href="/resep-masakan/resep.php?jenis=<?= urlencode($j['slug']) ?>"
               style="text-decoration:none;background:#fff;border-radius:18px;overflow:hidden;border:1.5px solid #e4ede6;transition:all 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:0 2px 8px rgba(45,106,79,0.05);display:block;"
               onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(45,106,79,0.13)';this.style.borderColor='#95d5b2'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.05)';this.style.borderColor='#e4ede6'">
                <div style="position:relative;height:110px;overflow:hidden;">
                    <?php if ($jimg): ?>
                    <img src="/resep-masakan/assets/images/kategori/<?= $jimg ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                         onmouseover="this.style.transform='scale(1.08)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.parentElement.style.background='#e8f5e8'">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,#d8f3dc,#95d5b2);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-utensils" style="font-size:2rem;color:#2d6a4f;opacity:0.4;"></i>
                    </div>
                    <?php endif; ?>
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(10,46,26,0.75) 0%,transparent 55%);"></div>
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:0.7rem;">
                        <div style="color:#fff;font-weight:700;font-size:0.82rem;"><?= htmlspecialchars($j['nama_jenis']) ?></div>
                        <div style="color:rgba(255,255,255,0.65);font-size:0.65rem;margin-top:1px;"><?= $j['total_resep'] ?> resep</div>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
