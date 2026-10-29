<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) { header('Location: /resep-masakan/login.php'); exit; }

$page_title = 'Profil Saya';
$user_id    = (int)$_SESSION['user_id'];
$error      = '';
$success    = '';

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt,'i',$user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stats = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as total_resep, COALESCE(SUM(views),0) as total_views,
     (SELECT COUNT(*) FROM favorit WHERE id_user=$user_id) as total_favorit,
     (SELECT COUNT(*) FROM komentar WHERE id_user=$user_id) as total_komentar
     FROM resep WHERE user_id=$user_id"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update_profil') {
        $nama = trim($_POST['nama'] ?? '');
        $bio  = trim($_POST['bio']  ?? '');
        if (strlen($nama) < 3) { $error = 'Nama minimal 3 karakter.'; }
        else {
            $foto = $user['foto'];
            if (!empty($_FILES['foto']['name'])) {
                $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg','jpeg','png','webp'];
                if (!in_array($ext,$allow)) { $error = 'Format foto tidak didukung.'; }
                elseif ($_FILES['foto']['size'] > 2*1024*1024) { $error = 'Ukuran foto maksimal 2MB.'; }
                else {
                    $filename = uniqid('profil_').'.'.$ext;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/profil/'.$filename)) {
                        if ($user['foto'] && $user['foto'] !== 'default.png') @unlink('../uploads/profil/'.$user['foto']);
                        $foto = $filename;
                    }
                }
            }
            if (!$error) {
                $upd = mysqli_prepare($koneksi, "UPDATE users SET nama=?,bio=?,foto=? WHERE id=?");
                mysqli_stmt_bind_param($upd,'sssi',$nama,$bio,$foto,$user_id);
                mysqli_stmt_execute($upd); mysqli_stmt_close($upd);
                $_SESSION['nama'] = $nama; $_SESSION['foto'] = $foto;
                $user['nama'] = $nama; $user['bio'] = $bio; $user['foto'] = $foto;
                $success = 'Profil berhasil diperbarui.';
            }
        }
    } elseif ($aksi === 'ganti_password') {
        $pw_lama  = $_POST['password_lama']    ?? '';
        $pw_baru  = $_POST['password_baru']    ?? '';
        $pw_konfirm = $_POST['password_konfirm'] ?? '';
        if (!password_verify($pw_lama, $user['password'])) { $error = 'Password lama tidak sesuai.'; }
        elseif (strlen($pw_baru) < 6) { $error = 'Password baru minimal 6 karakter.'; }
        elseif ($pw_baru !== $pw_konfirm) { $error = 'Konfirmasi password tidak cocok.'; }
        else {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $upd  = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($upd,'si',$hash,$user_id);
            mysqli_stmt_execute($upd); mysqli_stmt_close($upd);
            $success = 'Password berhasil diubah.';
        }
    }
}

require_once '../includes/header.php';
?>

<!-- HERO -->
<div style="background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);padding:3rem 0 6rem;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-80px;right:-80px;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.12) 0%,transparent 70%);pointer-events:none;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <!-- Avatar -->
            <div style="position:relative;">
                <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($user['foto'] ?? 'default.png') ?>"
                     id="avatar-preview"
                     style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid rgba(82,183,136,0.5);box-shadow:0 4px 20px rgba(0,0,0,0.3);"
                     onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                <div style="position:absolute;bottom:0;right:0;width:26px;height:26px;background:linear-gradient(135deg,#40916c,#52b788);border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid rgba(10,46,26,0.8);">
                    <i class="fas fa-camera" style="font-size:0.6rem;color:#fff;"></i>
                </div>
            </div>
            <div>
                <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;color:#fff;font-weight:700;letter-spacing:-0.5px;"><?= htmlspecialchars($user['nama']) ?></h1>
                <p style="color:rgba(255,255,255,0.5);font-size:0.875rem;margin-top:3px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-envelope" style="font-size:0.75rem;"></i><?= htmlspecialchars($user['email']) ?>
                    <span style="background:rgba(82,183,136,0.2);color:#95d5b2;padding:2px 10px;border-radius:20px;font-size:0.7rem;font-weight:600;text-transform:uppercase;"><?= ucfirst($user['role']) ?></span>
                </p>
                <p style="color:rgba(255,255,255,0.35);font-size:0.75rem;margin-top:4px;"><i class="fas fa-calendar-alt"></i> Bergabung <?= date('M Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
    </div>
    <div style="position:absolute;bottom:0;left:0;width:100%;overflow:hidden;line-height:0;">
        <svg viewBox="0 0 1440 50" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="display:block;width:100%;height:50px;">
            <path d="M0,25 C360,50 1080,0 1440,25 L1440,50 L0,50 Z" fill="#f4f7f4"/>
        </svg>
    </div>
</div>

<div style="background:#f4f7f4;padding:2rem 0 5rem;">
<div class="container" style="max-width:900px;">

    <?php if ($error): ?>
    <div style="display:flex;align-items:center;gap:8px;background:rgba(231,76,60,0.08);border:1.5px solid rgba(231,76,60,0.2);border-radius:12px;padding:12px 16px;font-size:0.875rem;color:#c0392b;margin-bottom:1.5rem;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div style="display:flex;align-items:center;gap:8px;background:rgba(45,106,79,0.08);border:1.5px solid rgba(45,106,79,0.2);border-radius:12px;padding:12px 16px;font-size:0.875rem;color:#2d6a4f;margin-bottom:1.5rem;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;">
        <?php
        $si = [
            ['label'=>'Resep Dibuat','value'=>$stats['total_resep'],'icon'=>'fa-book-open','bg'=>'rgba(45,106,79,0.1)','color'=>'#2d6a4f'],
            ['label'=>'Total Views', 'value'=>number_format($stats['total_views']),'icon'=>'fa-eye','bg'=>'rgba(52,152,219,0.1)','color'=>'#2471a3'],
            ['label'=>'Favorit',     'value'=>$stats['total_favorit'],'icon'=>'fa-heart','bg'=>'rgba(231,76,60,0.1)','color'=>'#c0392b'],
            ['label'=>'Komentar',    'value'=>$stats['total_komentar'],'icon'=>'fa-comment','bg'=>'rgba(243,156,18,0.1)','color'=>'#b7770d'],
        ];
        foreach ($si as $s):
        ?>
        <div style="background:#fff;border-radius:16px;padding:1.2rem;display:flex;align-items:center;gap:12px;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <div style="width:44px;height:44px;background:<?= $s['bg'] ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;font-size:1rem;"></i>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:700;color:#1a2e1a;letter-spacing:-0.5px;line-height:1;"><?= $s['value'] ?></div>
                <div style="font-size:0.72rem;color:#8a9e87;margin-top:2px;"><?= $s['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

        <!-- EDIT PROFIL -->
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h3 style="font-size:1rem;font-weight:700;color:#1a2e1a;margin-bottom:1.5rem;display:flex;align-items:center;gap:8px;padding-bottom:1rem;border-bottom:1px solid #f0f5f0;">
                <i class="fas fa-user-edit" style="color:#2d6a4f;"></i> Edit Profil
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="update_profil">

                <!-- Upload foto -->
                <div style="margin-bottom:1.2rem;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;">Foto Profil</label>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($user['foto'] ?? 'default.png') ?>"
                             id="preview-img"
                             style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #c8ddc8;flex-shrink:0;"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <div style="flex:1;">
                            <input type="file" name="foto" accept="image/*"
                                   onchange="previewFoto(this)"
                                   style="width:100%;font-size:0.8rem;color:#4a6741;">
                            <small style="color:#8a9e87;font-size:0.72rem;">JPG/PNG/WEBP, maks 2MB</small>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px;">Nama Lengkap *</label>
                    <input type="text" name="nama" required
                           value="<?= htmlspecialchars($user['nama']) ?>"
                           style="width:100%;padding:10px 14px;background:#f4f7f4;border:1.5px solid #d4e6d4;border-radius:12px;font-size:0.875rem;font-family:inherit;color:#1a2e1a;outline:none;transition:border-color 0.2s;"
                           onfocus="this.style.borderColor='#40916c'"
                           onblur="this.style.borderColor='#d4e6d4'">
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px;">Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                           style="width:100%;padding:10px 14px;background:#f0f5f0;border:1.5px solid #e4ede6;border-radius:12px;font-size:0.875rem;color:#8a9e87;cursor:not-allowed;">
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px;">Bio</label>
                    <textarea name="bio" rows="3" placeholder="Ceritakan tentang dirimu..."
                              style="width:100%;padding:10px 14px;background:#f4f7f4;border:1.5px solid #d4e6d4;border-radius:12px;font-size:0.875rem;font-family:inherit;color:#1a2e1a;outline:none;resize:none;transition:border-color 0.2s;"
                              onfocus="this.style.borderColor='#40916c'"
                              onblur="this.style.borderColor='#d4e6d4'"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <button type="submit" style="width:100%;padding:11px;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;border:none;border-radius:12px;font-size:0.875rem;font-weight:600;cursor:pointer;font-family:inherit;box-shadow:0 4px 12px rgba(45,106,79,0.25);transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:8px;"
                        onmouseover="this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.transform=''">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- GANTI PASSWORD -->
        <div style="background:#fff;border-radius:20px;padding:2rem;border:1.5px solid #e4ede6;box-shadow:0 2px 8px rgba(45,106,79,0.05);">
            <h3 style="font-size:1rem;font-weight:700;color:#1a2e1a;margin-bottom:1.5rem;display:flex;align-items:center;gap:8px;padding-bottom:1rem;border-bottom:1px solid #f0f5f0;">
                <i class="fas fa-lock" style="color:#2d6a4f;"></i> Ganti Password
            </h3>
            <form method="POST">
                <input type="hidden" name="aksi" value="ganti_password">

                <?php foreach ([
                    ['password_lama',   'Password Lama *',      'Masukkan password lama'],
                    ['password_baru',   'Password Baru *',      'Minimal 6 karakter'],
                    ['password_konfirm','Konfirmasi Baru *',    'Ulangi password baru'],
                ] as [$name,$label,$placeholder]): ?>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.72rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px;"><?= $label ?></label>
                    <input type="password" name="<?= $name ?>" required placeholder="<?= $placeholder ?>"
                           style="width:100%;padding:10px 14px;background:#f4f7f4;border:1.5px solid #d4e6d4;border-radius:12px;font-size:0.875rem;font-family:inherit;color:#1a2e1a;outline:none;transition:border-color 0.2s;"
                           onfocus="this.style.borderColor='#40916c'"
                           onblur="this.style.borderColor='#d4e6d4'">
                </div>
                <?php endforeach; ?>

                <button type="submit" style="width:100%;padding:11px;background:#f4f7f4;color:#2d6a4f;border:1.5px solid #d4e6d4;border-radius:12px;font-size:0.875rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:0.5rem;"
                        onmouseover="this.style.background='#d8f3dc';this.style.borderColor='#40916c'"
                        onmouseout="this.style.background='#f4f7f4';this.style.borderColor='#d4e6d4'">
                    <i class="fas fa-key"></i> Ganti Password
                </button>
            </form>

            <!-- Quick links -->
            <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #f0f5f0;">
                <p style="font-size:0.75rem;font-weight:700;color:#8a9e87;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;">Navigasi Cepat</p>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <a href="/resep-masakan/user/resep-saya.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f4f7f4;border-radius:10px;text-decoration:none;color:#1a2e1a;font-size:0.85rem;font-weight:500;transition:background 0.2s;"
                       onmouseover="this.style.background='#e8f5e8'"
                       onmouseout="this.style.background='#f4f7f4'">
                        <i class="fas fa-book-open" style="color:#2d6a4f;width:16px;text-align:center;"></i> Resep Saya
                    </a>
                    <a href="/resep-masakan/user/favorit.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f4f7f4;border-radius:10px;text-decoration:none;color:#1a2e1a;font-size:0.85rem;font-weight:500;transition:background 0.2s;"
                       onmouseover="this.style.background='#e8f5e8'"
                       onmouseout="this.style.background='#f4f7f4'">
                        <i class="fas fa-heart" style="color:#e74c3c;width:16px;text-align:center;"></i> Favorit Saya
                    </a>
                    <a href="/resep-masakan/user/tambah-resep.php" style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f4f7f4;border-radius:10px;text-decoration:none;color:#1a2e1a;font-size:0.85rem;font-weight:500;transition:background 0.2s;"
                       onmouseover="this.style.background='#e8f5e8'"
                       onmouseout="this.style.background='#f4f7f4'">
                        <i class="fas fa-plus" style="color:#2d6a4f;width:16px;text-align:center;"></i> Tambah Resep
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<script>
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('preview-img').src  = e.target.result;
            document.getElementById('avatar-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
