<?php
session_start();
require_once 'config/koneksi.php';

$page_title = 'Beranda';

// Resep terbaru
$resep_terbaru = [];
$result = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id=u.id
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE r.status='publik'
     GROUP BY r.id ORDER BY r.created_at DESC LIMIT 6");
if ($result) while ($row = mysqli_fetch_assoc($result)) $resep_terbaru[] = $row;

// Resep populer
$resep_populer = [];
$result2 = mysqli_query($koneksi,
    "SELECT r.*, u.nama as nama_user, k.nama_kategori,
     COALESCE(AVG(rt.nilai),0) as rata_rating,
     COUNT(DISTINCT rt.id) as jumlah_rating
     FROM resep r
     LEFT JOIN users u ON r.user_id=u.id
     LEFT JOIN kategori k ON r.id_kategori=k.id
     LEFT JOIN rating rt ON r.id=rt.id_resep
     WHERE r.status='publik'
     GROUP BY r.id ORDER BY r.views DESC LIMIT 4");
if ($result2) while ($row = mysqli_fetch_assoc($result2)) $resep_populer[] = $row;

// Kategori
$kategori_list = [];
$result3 = mysqli_query($koneksi,
    "SELECT k.*, COUNT(r.id) as jumlah_resep
     FROM kategori k
     LEFT JOIN resep r ON k.id=r.id_kategori AND r.status='publik'
     GROUP BY k.id ORDER BY jumlah_resep DESC LIMIT 8");
if ($result3) while ($row = mysqli_fetch_assoc($result3)) $kategori_list[] = $row;

// Statistik
$total_resep    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM resep WHERE status='publik'"))['t'] ?? 0;
$total_user     = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM users WHERE role='user'"))['t'] ?? 0;
$total_kategori = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM kategori"))['t'] ?? 0;

require_once 'includes/header.php';
?>

<!-- ===================================================================
     HERO
     =================================================================== -->
<section style="
    background: linear-gradient(160deg, #0a2e1a 0%, #1a4731 40%, #2d6a4f 100%);
    min-height: 92vh;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    padding: 4rem 0;
">
    <!-- Dekorasi background -->
    <div style="position:absolute;inset:0;background:url('data:image/svg+xml,%3Csvg width=\'80\' height=\'80\' viewBox=\'0 0 80 80\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%2352b788\' fill-opacity=\'0.04\'%3E%3Cpath d=\'M0 0h40v40H0V0zm40 40h40v40H40V40zm0-40h2l-2 2V0zm0 4l4-4h2l-6 6V4zm0 4l8-8h2L40 10V8zm0 4L52 0h2L40 14v-2zm0 4L56 0h2L40 18v-2zm0 4L60 0h2L40 22v-2zm0 4L64 0h2L40 26v-2zm0 4L68 0h2L40 30v-2zm0 4L72 0h2L40 34v-2zm0 4L76 0h2L40 38v-2zm0 4L80 0v2L42 40h-2zm4 0L80 4v2L46 40h-2zm4 0L80 8v2L50 40h-2zm4 0L80 12v2L54 40h-2zm4 0L80 16v2L58 40h-2zm4 0L80 20v2L62 40h-2zm4 0L80 24v2L66 40h-2zm4 0L80 28v2L70 40h-2zm4 0L80 32v2L74 40h-2zm4 0L80 36v2L78 40h-2zm4 0L80 40v-2h-2zm-4 0h2v2L80 40h-2z\'/%3E%3C/g%3E%3C/svg%3E') repeat;
    opacity:0.4; pointer-events:none;"></div>

    <!-- Blob dekorasi -->
    <div style="position:absolute;top:-200px;right:-200px;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.15) 0%,transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-150px;left:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(149,213,178,0.1) 0%,transparent 70%);pointer-events:none;"></div>

    <div class="container" style="position:relative;z-index:2;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:center;">

            <!-- KIRI -->
            <div>
                <!-- Tag -->
                <div style="
                    display:inline-flex;align-items:center;gap:8px;
                    background:rgba(82,183,136,0.15);border:1px solid rgba(82,183,136,0.3);
                    padding:6px 16px;border-radius:30px;
                    font-size:0.75rem;font-weight:600;color:#95d5b2;
                    letter-spacing:1px;text-transform:uppercase;
                    margin-bottom:1.8rem;
                ">
                    <span style="width:6px;height:6px;background:#52b788;border-radius:50%;display:inline-block;animation:pulseGreen 2s ease-in-out infinite;"></span>
                    Platform Resep Terlengkap
                </div>

                <!-- Heading -->
                <h1 style="
                    font-family:'Playfair Display',serif;
                    font-size:clamp(2.8rem,5vw,4rem);
                    line-height:1.12;
                    color:#fff;
                    font-weight:700;
                    margin-bottom:1.5rem;
                    letter-spacing:-0.5px;
                ">
                    Masak Lebih<br>
                    <span style="
                        background:linear-gradient(135deg,#52b788,#95d5b2);
                        -webkit-background-clip:text;
                        -webkit-text-fill-color:transparent;
                        background-clip:text;
                    ">Menyenangkan.</span>
                </h1>

                <p style="color:rgba(255,255,255,0.65);font-size:1rem;line-height:1.8;max-width:460px;margin-bottom:2.5rem;">
                    Temukan ribuan resep masakan Indonesia dari berbagai kategori. Dari sarapan pagi hingga dessert malam — semua ada di sini.
                </p>

                <!-- CTA -->
                <div style="display:flex;gap:1rem;margin-bottom:3rem;flex-wrap:wrap;">
                    <a href="/resep-masakan/resep.php" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:linear-gradient(135deg,#52b788,#2d6a4f);
                        color:#fff;padding:14px 28px;border-radius:50px;
                        font-size:0.9rem;font-weight:600;text-decoration:none;
                        box-shadow:0 8px 24px rgba(82,183,136,0.4);
                        transition:all 0.3s ease;
                    " onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 12px 32px rgba(82,183,136,0.5)'"
                       onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(82,183,136,0.4)'">
                        <i class="fas fa-utensils"></i> Jelajahi Resep
                    </a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="/resep-masakan/register.php" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:rgba(255,255,255,0.08);
                        color:#fff;padding:14px 28px;border-radius:50px;
                        font-size:0.9rem;font-weight:600;text-decoration:none;
                        border:1.5px solid rgba(255,255,255,0.2);
                        transition:all 0.3s ease;
                        backdrop-filter:blur(10px);
                    " onmouseover="this.style.background='rgba(255,255,255,0.15)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                        <i class="fas fa-user-plus"></i> Bergabung Gratis
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div style="display:flex;gap:2rem;">
                    <?php
                    $stats_hero = [
                        [$total_resep.'+', 'Resep'],
                        [$total_user.'+', 'Member'],
                        [$total_kategori.'+', 'Kategori'],
                    ];
                    foreach ($stats_hero as $sh):
                    ?>
                    <div>
                        <div style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;color:#fff;"><?= $sh[0] ?></div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:1px;"><?= $sh[1] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- KANAN: Foto + floating cards -->
            <div style="position:relative;display:flex;justify-content:center;align-items:center;">
                <!-- Lingkaran cahaya -->
                <div style="position:absolute;width:380px;height:380px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.2) 0%,transparent 70%);z-index:0;"></div>

                <!-- Foto utama -->
                <img src="https://images.unsplash.com/photo-1547592180-85f173990554?w=600&q=85"
                     alt="Masakan Lezat"
                     style="
                        width:360px;height:420px;
                        object-fit:cover;
                        border-radius:28px;
                        position:relative;z-index:2;
                        animation:float 6s ease-in-out infinite;
                        box-shadow:0 40px 80px rgba(0,0,0,0.4);
                     "
                     onerror="this.src='https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&q=80'">

                <!-- Floating card — Rating -->
                <div style="
                    position:absolute;top:30px;right:-20px;
                    background:rgba(255,255,255,0.95);
                    border-radius:16px;padding:12px 16px;
                    box-shadow:0 16px 40px rgba(0,0,0,0.15);
                    z-index:3;min-width:150px;
                    animation:float 4s ease-in-out infinite 1s;
                    backdrop-filter:blur(10px);
                ">
                    <div style="font-size:0.68rem;color:#999;margin-bottom:5px;font-weight:500;">RATING RATA-RATA</div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.9rem;">
                            <?php for($s=0;$s<5;$s++) echo '<i class="fas fa-star"></i>'; ?>
                        </div>
                        <strong style="font-size:1rem;color:#1a2e1a;">4.9</strong>
                    </div>
                    <div style="font-size:0.7rem;color:#888;margin-top:3px;"><?= $total_resep ?>+ ulasan</div>
                </div>

                <!-- Floating card — Resep baru -->
                <div style="
                    position:absolute;bottom:40px;left:-30px;
                    background:rgba(255,255,255,0.95);
                    border-radius:16px;padding:14px 16px;
                    box-shadow:0 16px 40px rgba(0,0,0,0.15);
                    z-index:3;min-width:180px;
                    animation:float 4s ease-in-out infinite 0.5s;
                    backdrop-filter:blur(10px);
                ">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;background:linear-gradient(135deg,#52b788,#2d6a4f);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-fire" style="color:#fff;font-size:0.85rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:0.7rem;color:#999;font-weight:500;">RESEP POPULER</div>
                            <div style="font-size:0.85rem;font-weight:700;color:#1a2e1a;"><?= $total_resep ?>+ Resep</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wave bottom -->
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:60px;">
            <path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" fill="#f8faf8"/>
        </svg>
    </div>
</section>

<!-- ===================================================================
     KATEGORI
     =================================================================== -->
<section style="padding:5rem 0;background:#f8faf8;">
    <div class="container">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2.5rem;">
            <div>
                <p style="font-size:0.75rem;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Eksplorasi</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--text-primary);font-weight:700;letter-spacing:-0.5px;">
                    Pilih <span style="color:var(--green);">Kategori</span> Favoritmu
                </h2>
            </div>
            <a href="/resep-masakan/kategori.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--green);font-size:0.875rem;font-weight:600;text-decoration:none;border:1.5px solid var(--green);padding:8px 18px;border-radius:20px;transition:all 0.3s;"
               onmouseover="this.style.background='var(--green)';this.style.color='#fff'"
               onmouseout="this.style.background='';this.style.color='var(--green)'">
                Semua Kategori <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;">
            <?php
            $kat_img = [
                'ayam'=>'kat-ayam.jpg','daging-sapi'=>'kat-daging.jpg',
                'ikan'=>'kat-ikan.jpg','seafood'=>'kat-seafood.jpg',
                'sayuran'=>'kat-sayuran.jpg','tahu-tempe'=>'kat-tahu.jpg',
                'telur'=>'kat-telur.jpg','kambing'=>'kat-kambing.jpg',
                'bebek'=>'kat-bebek.jpg','buah-buahan'=>'kat-buah.jpg',
                'mie-pasta'=>'kat-mie.jpg','nasi'=>'kat-nasi.jpg',
            ];
            foreach ($kategori_list as $kat):
                $img = $kat_img[$kat['slug']] ?? null;
            ?>
            <a href="/resep-masakan/resep.php?kategori=<?= urlencode($kat['slug']) ?>"
               style="
                text-decoration:none;display:block;
                background:#fff;border-radius:20px;
                overflow:hidden;border:1.5px solid #e8f0e8;
                transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
                box-shadow:0 2px 8px rgba(45,106,79,0.06);
               "
               onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 16px 40px rgba(45,106,79,0.15)';this.style.borderColor='var(--green-light)'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.06)';this.style.borderColor='#e8f0e8'">
                <!-- Gambar -->
                <div style="height:110px;overflow:hidden;position:relative;">
                    <?php if ($img): ?>
                    <img src="/resep-masakan/assets/images/kategori/<?= $img ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;"
                         onmouseover="this.style.transform='scale(1.08)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.parentElement.style.background='#e8f5e8'">
                    <?php else: ?>
                    <div style="width:100%;height:100%;background:linear-gradient(135deg,#d8f3dc,#95d5b2);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-utensils" style="font-size:2rem;color:var(--green);opacity:0.5;"></i>
                    </div>
                    <?php endif; ?>
                    <!-- Overlay gradient -->
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(26,71,49,0.6) 0%,transparent 60%);"></div>
                    <!-- Nama di atas gambar -->
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:0.8rem;">
                        <div style="color:#fff;font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                        <div style="color:rgba(255,255,255,0.7);font-size:0.72rem;"><?= $kat['jumlah_resep'] ?> resep</div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================================================================
     RESEP TERBARU
     =================================================================== -->
<section style="padding:5rem 0;background:#fff;">
    <div class="container">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2.5rem;">
            <div>
                <p style="font-size:0.75rem;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Baru Ditambahkan</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:2rem;color:var(--text-primary);font-weight:700;letter-spacing:-0.5px;">
                    Resep <span style="color:var(--green);">Terbaru</span>
                </h2>
            </div>
            <a href="/resep-masakan/resep.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--green);font-size:0.875rem;font-weight:600;text-decoration:none;border:1.5px solid var(--green);padding:8px 18px;border-radius:20px;transition:all 0.3s;"
               onmouseover="this.style.background='var(--green)';this.style.color='#fff'"
               onmouseout="this.style.background='';this.style.color='var(--green)'">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($resep_terbaru)): ?>
        <div style="text-align:center;padding:5rem;background:#f8faf8;border-radius:20px;">
            <i class="fas fa-utensils" style="font-size:3rem;color:#d4e6d4;display:block;margin-bottom:1rem;"></i>
            <p style="color:var(--text-muted);">Belum ada resep. <a href="/resep-masakan/<?= isset($_SESSION['user_id'])?'user/tambah-resep.php':'register.php' ?>" style="color:var(--green);font-weight:600;">Jadilah yang pertama!</a></p>
        </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;">
            <?php foreach ($resep_terbaru as $resep): ?>
            <div onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'"
                 style="
                    background:#fff;border-radius:20px;overflow:hidden;
                    border:1.5px solid #e8f0e8;cursor:pointer;
                    transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
                    box-shadow:0 2px 8px rgba(45,106,79,0.06);
                    display:flex;flex-direction:column;
                 "
                 onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 20px 50px rgba(45,106,79,0.15)';this.style.borderColor='var(--green-light)'"
                 onmouseout="this.style.transform='';this.style.boxShadow='0 2px 8px rgba(45,106,79,0.06)';this.style.borderColor='#e8f0e8'">
                <!-- Gambar -->
                <div style="position:relative;overflow:hidden;height:200px;flex-shrink:0;">
                    <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s ease;"
                         onmouseover="this.style.transform='scale(1.05)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                    <!-- Badge kesulitan -->
                    <span style="
                        position:absolute;top:12px;left:12px;
                        background:<?= $resep['tingkat_kesulitan']==='mudah'?'#2d6a4f':($resep['tingkat_kesulitan']==='sedang'?'#d68910':'#c0392b') ?>;
                        color:#fff;padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:600;
                    "><?= ucfirst($resep['tingkat_kesulitan']) ?></span>
                    <!-- Waktu masak -->
                    <span style="
                        position:absolute;bottom:12px;right:12px;
                        background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);
                        color:#fff;padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:500;
                    "><i class="fas fa-clock"></i> <?= $resep['waktu_masak'] ?> mnt</span>
                </div>

                <!-- Konten -->
                <div style="padding:1.2rem;flex:1;display:flex;flex-direction:column;">
                    <div style="font-size:0.72rem;color:var(--green);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
                        <?= htmlspecialchars($resep['nama_kategori']) ?>
                    </div>
                    <h3 style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:0.8rem;line-height:1.4;flex:1;
                        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($resep['judul']) ?>
                    </h3>
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:5px;">
                            <?php
                            $rat = round($resep['rata_rating']);
                            echo '<div style="display:flex;gap:2px;color:#f59e0b;font-size:0.75rem;">';
                            for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:#ddd;"></i>';
                            echo '</div>';
                            ?>
                            <span style="font-size:0.72rem;color:var(--text-muted);">(<?= $resep['jumlah_rating'] ?>)</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;font-size:0.75rem;color:var(--text-muted);">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars(mb_strimwidth($resep['nama_user'],0,15,'...')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================================================================
     RESEP POPULER
     =================================================================== -->
<?php if (!empty($resep_populer)): ?>
<section style="padding:5rem 0;background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 60%,#2d6a4f 100%);position:relative;overflow:hidden;">
    <!-- Pattern bg -->
    <div style="position:absolute;inset:0;background:url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%2352b788\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/svg%3E') repeat;opacity:0.5;pointer-events:none;"></div>
    <div style="position:absolute;top:-100px;right:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>

    <div class="container" style="position:relative;z-index:1;">
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2.5rem;">
            <div>
                <p style="font-size:0.75rem;font-weight:600;color:#52b788;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Trending</p>
                <h2 style="font-family:'Playfair Display',serif;font-size:2rem;color:#fff;font-weight:700;letter-spacing:-0.5px;">
                    Resep <span style="color:#95d5b2;">Populer</span>
                </h2>
            </div>
            <a href="/resep-masakan/populer.php" style="display:inline-flex;align-items:center;gap:6px;color:#95d5b2;font-size:0.875rem;font-weight:600;text-decoration:none;border:1.5px solid rgba(149,213,178,0.4);padding:8px 18px;border-radius:20px;transition:all 0.3s;"
               onmouseover="this.style.background='rgba(149,213,178,0.1)'"
               onmouseout="this.style.background=''">
                Lihat Semua <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;">
            <?php foreach ($resep_populer as $i => $resep): ?>
            <div onclick="window.location='/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>'"
                 style="
                    background:rgba(255,255,255,0.06);border-radius:20px;overflow:hidden;
                    border:1.5px solid rgba(255,255,255,0.1);cursor:pointer;
                    transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
                    backdrop-filter:blur(10px);
                 "
                 onmouseover="this.style.transform='translateY(-6px)';this.style.background='rgba(255,255,255,0.1)';this.style.borderColor='rgba(149,213,178,0.3)'"
                 onmouseout="this.style.transform='';this.style.background='rgba(255,255,255,0.06)';this.style.borderColor='rgba(255,255,255,0.1)'">
                <!-- Gambar -->
                <div style="position:relative;overflow:hidden;height:170px;">
                    <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.5s;"
                         onmouseover="this.style.transform='scale(1.05)'"
                         onmouseout="this.style.transform=''"
                         onerror="this.src='/resep-masakan/assets/images/default-resep.svg'">
                    <!-- Rank -->
                    <div style="
                        position:absolute;top:10px;left:10px;
                        width:30px;height:30px;
                        background:<?= $i===0?'linear-gradient(135deg,#f59e0b,#d97706)':($i===1?'linear-gradient(135deg,#9ca3af,#6b7280)':($i===2?'linear-gradient(135deg,#cd7f32,#a0522d)':'rgba(255,255,255,0.2)')) ?>;
                        border-radius:8px;display:flex;align-items:center;justify-content:center;
                        font-weight:700;font-size:0.8rem;color:#fff;
                        backdrop-filter:blur(10px);
                    ">#<?= $i+1 ?></div>
                    <!-- Views -->
                    <div style="position:absolute;bottom:10px;right:10px;background:rgba(0,0,0,0.6);color:#fff;padding:3px 8px;border-radius:12px;font-size:0.68rem;">
                        <i class="fas fa-eye"></i> <?= number_format($resep['views']) ?>
                    </div>
                </div>
                <!-- Info -->
                <div style="padding:1rem;">
                    <div style="font-size:0.7rem;color:#95d5b2;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                        <?= htmlspecialchars($resep['nama_kategori']) ?>
                    </div>
                    <h3 style="font-size:0.875rem;font-weight:600;color:#fff;margin-bottom:8px;line-height:1.4;
                        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($resep['judul']) ?>
                    </h3>
                    <div style="display:flex;gap:2px;color:#f59e0b;font-size:0.75rem;">
                        <?php
                        $rat=round($resep['rata_rating']);
                        for($s=1;$s<=5;$s++) echo $s<=$rat?'<i class="fas fa-star"></i>':'<i class="far fa-star" style="color:rgba(255,255,255,0.2);"></i>';
                        ?>
                        <span style="color:rgba(255,255,255,0.45);font-size:0.7rem;margin-left:4px;">(<?= $resep['jumlah_rating'] ?>)</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===================================================================
     CTA SECTION
     =================================================================== -->
<?php if (!isset($_SESSION['user_id'])): ?>
<section style="padding:6rem 0;background:#f8faf8;">
    <div class="container">
        <div style="
            background:linear-gradient(135deg,#0a2e1a 0%,#2d6a4f 100%);
            border-radius:28px;padding:5rem;text-align:center;
            position:relative;overflow:hidden;
        ">
            <!-- Dekorasi -->
            <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.15) 0%,transparent 70%);pointer-events:none;"></div>
            <div style="position:absolute;bottom:-80px;left:-80px;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(149,213,178,0.1) 0%,transparent 70%);pointer-events:none;"></div>

            <div style="position:relative;z-index:1;">
                <div style="width:64px;height:64px;background:rgba(82,183,136,0.2);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                    <i class="fas fa-utensils" style="font-size:1.8rem;color:#95d5b2;"></i>
                </div>
                <h2 style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#fff;font-weight:700;margin-bottom:1rem;letter-spacing:-0.5px;">
                    Punya Resep Spesial?
                </h2>
                <p style="color:rgba(255,255,255,0.6);max-width:500px;margin:0 auto 2.5rem;line-height:1.8;">
                    Bergabung dan bagikan resep andalanmu kepada jutaan pecinta kuliner di seluruh Indonesia.
                </p>
                <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                    <a href="/resep-masakan/register.php" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:linear-gradient(135deg,#52b788,#40916c);
                        color:#fff;padding:14px 32px;border-radius:50px;
                        font-size:0.9rem;font-weight:600;text-decoration:none;
                        box-shadow:0 8px 24px rgba(82,183,136,0.4);
                        transition:all 0.3s;
                    " onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform=''">
                        <i class="fas fa-user-plus"></i> Daftar Gratis
                    </a>
                    <a href="/resep-masakan/login.php" style="
                        display:inline-flex;align-items:center;gap:8px;
                        background:rgba(255,255,255,0.1);
                        color:#fff;padding:14px 32px;border-radius:50px;
                        font-size:0.9rem;font-weight:600;text-decoration:none;
                        border:1.5px solid rgba(255,255,255,0.2);
                        transition:all 0.3s;
                    " onmouseover="this.style.background='rgba(255,255,255,0.18)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        <i class="fas fa-sign-in-alt"></i> Sudah Punya Akun
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
