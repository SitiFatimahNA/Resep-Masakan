<?php
session_start();
require_once 'config/koneksi.php';
$page_title = 'Kategori';

$kategori_list = mysqli_query($koneksi,
    "SELECT k.*,
     COUNT(r.id) as total_resep,
     SUM(r.views) as total_views
     FROM kategori k
     LEFT JOIN resep r ON k.id=r.id_kategori AND r.status='publik'
     GROUP BY k.id
     ORDER BY total_resep DESC");

$jenis_list = mysqli_query($koneksi,
    "SELECT j.*, COUNT(r.id) as total_resep
     FROM jenis_masakan j
     LEFT JOIN resep r ON j.id=r.id_jenis AND r.status='publik'
     GROUP BY j.id
     ORDER BY total_resep DESC");

require_once 'includes/header.php';
?>

<div style="background:linear-gradient(135deg, #f0f7f0 0%, #f8faf8 50%, #f0f5f0 100%); min-height:100vh; padding:2rem 0 4rem;">
<div class="container">

    <!-- Kategori Bahan -->
    <div style="margin-bottom:3rem;">
        <div class="section-header animate">
            <div>
                <h1 style="font-family:'Playfair Display',serif; font-size:2rem; color:var(--text-primary);">
                    Kategori <span style="color:var(--green);">Bahan</span>
                </h1>
                <div class="divider"></div>
                <p style="color:var(--text-muted);">Temukan resep berdasarkan bahan utama</p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:1.2rem;">
            <?php
            $kat_img = [
                'ayam'=>'kat-ayam.jpg','daging-sapi'=>'kat-daging.jpg',
                'ikan'=>'kat-ikan.jpg','seafood'=>'kat-seafood.jpg',
                'sayuran'=>'kat-sayuran.jpg','tahu-tempe'=>'kat-tahu.jpg',
                'telur'=>'kat-telur.jpg','kambing'=>'kat-kambing.jpg',
                'bebek'=>'kat-bebek.jpg','buah-buahan'=>'kat-buah.jpg',
                'mie-pasta'=>'kat-mie.jpg','nasi'=>'kat-nasi.jpg',
            ];
            $i = 0;
            while ($k = mysqli_fetch_assoc($kategori_list)):
                $img = $kat_img[$k['slug']] ?? null;
                $i++;
            ?>
            <a href="/resep-masakan/resep.php?kategori=<?= urlencode($k['slug']) ?>"
               class="card animate animate-delay-<?= ($i%4)+1 ?>"
               style="text-decoration:none; display:block;">
                <div style="overflow:hidden; height:140px; position:relative;">
                    <?php if ($img): ?>
                    <img src="/resep-masakan/assets/images/kategori/<?= $img ?>"
                         style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease;"
                         onmouseover="this.style.transform='scale(1.08)'"
                         onmouseout="this.style.transform='scale(1)'"
                         onerror="this.parentElement.style.background='var(--bg-secondary)'">
                    <?php else: ?>
                    <div style="width:100%; height:100%; background:var(--bg-secondary); display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-utensils" style="font-size:2rem; color:var(--green-light);"></i>
                    </div>
                    <?php endif; ?>
                    <!-- Overlay -->
                    <div style="position:absolute; bottom:0; left:0; right:0; background:linear-gradient(transparent, rgba(0,0,0,0.6)); padding:1rem 0.8rem 0.6rem;">
                        <div style="color:#fff; font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($k['nama_kategori']) ?></div>
                        <div style="color:rgba(255,255,255,0.8); font-size:0.75rem;"><?= $k['total_resep'] ?> resep</div>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Jenis Masakan -->
    <div>
        <div class="section-header animate">
            <div>
                <h2 style="font-family:'Playfair Display',serif; font-size:1.6rem; color:var(--text-primary);">
                    Jenis <span style="color:var(--green);">Masakan</span>
                </h2>
                <div class="divider"></div>
                <p style="color:var(--text-muted);">Cari resep berdasarkan waktu makan atau jenis sajian</p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:1rem;">
            <?php
            $jenis_img = [
                'sarapan'        => 'jenis-sarapan.jpg',
                'makan-siang'    => 'jenis-makan-siang.jpg',
                'makan-malam'    => 'jenis-makan-malam.jpg',
                'camilan'        => 'jenis-camilan.jpg',
                'minuman'        => 'jenis-minuman.jpg',
                'dessert'        => 'jenis-dessert.jpg',
                'sup-soto'       => 'jenis-sup-soto.jpg',
                'bakar-panggang' => 'jenis-bakar-panggang.jpg',
                'goreng'         => 'jenis-goreng.jpg',
                'rebus-kukus'    => 'jenis-rebus-kukus.jpg',
            ];
            $i=0; while ($j = mysqli_fetch_assoc($jenis_list)): $i++;
                $jimg = $jenis_img[$j['slug']] ?? null;
            ?>
            <a href="/resep-masakan/resep.php?jenis=<?= urlencode($j['slug']) ?>"
               class="card animate animate-delay-<?= ($i%4)+1 ?>"
               style="text-decoration:none; display:block;">
                <div style="overflow:hidden; height:120px; position:relative;">
                    <?php if ($jimg): ?>
                    <img src="/resep-masakan/assets/images/kategori/<?= $jimg ?>"
                         style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease;"
                         onmouseover="this.style.transform='scale(1.08)'"
                         onmouseout="this.style.transform='scale(1)'"
                         onerror="this.parentElement.style.background='var(--bg-secondary)'; this.style.display='none';">
                    <?php else: ?>
                    <div style="width:100%; height:100%; background:var(--green-pale); display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-utensils" style="font-size:2rem; color:var(--green-light);"></i>
                    </div>
                    <?php endif; ?>
                    <!-- Overlay gelap -->
                    <div style="position:absolute; inset:0; background:linear-gradient(transparent 40%, rgba(0,0,0,0.65)); pointer-events:none;"></div>
                    <!-- Teks di atas gambar -->
                    <div style="position:absolute; bottom:0; left:0; right:0; padding:0.6rem 0.8rem;">
                        <div style="color:#fff; font-weight:600; font-size:0.85rem; text-shadow:0 1px 3px rgba(0,0,0,0.5);">
                            <?= htmlspecialchars($j['nama_jenis']) ?>
                        </div>
                        <div style="color:rgba(255,255,255,0.8); font-size:0.72rem;">
                            <?= $j['total_resep'] ?> resep
                        </div>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
