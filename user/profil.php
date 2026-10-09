<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/login.php');
    exit;
}

$page_title = 'Profil Saya';
$user_id    = (int)$_SESSION['user_id'];
$error      = '';
$success    = '';

// Ambil data user
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Statistik user
$stats = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as total_resep,
     SUM(views) as total_views,
     (SELECT COUNT(*) FROM favorit WHERE id_user=$user_id) as total_favorit,
     (SELECT COUNT(*) FROM komentar WHERE id_user=$user_id) as total_komentar
     FROM resep WHERE user_id=$user_id"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update_profil') {
        $nama = trim($_POST['nama'] ?? '');
        $bio  = trim($_POST['bio']  ?? '');

        if (strlen($nama) < 3) {
            $error = 'Nama minimal 3 karakter.';
        } else {
            $foto = $user['foto'];

            // Upload foto baru
            if (!empty($_FILES['foto']['name'])) {
                $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $allow)) {
                    $error = 'Format foto tidak didukung.';
                } elseif ($_FILES['foto']['size'] > 2*1024*1024) {
                    $error = 'Ukuran foto maksimal 2MB.';
                } else {
                    $filename = uniqid('profil_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], '../uploads/profil/' . $filename)) {
                        // Hapus foto lama
                        if ($user['foto'] && $user['foto'] !== 'default.png') {
                            @unlink('../uploads/profil/' . $user['foto']);
                        }
                        $foto = $filename;
                    }
                }
            }

            if (!$error) {
                $upd = mysqli_prepare($koneksi, "UPDATE users SET nama=?, bio=?, foto=? WHERE id=?");
                mysqli_stmt_bind_param($upd, 'sssi', $nama, $bio, $foto, $user_id);
                mysqli_stmt_execute($upd);
                mysqli_stmt_close($upd);

                // Update session
                $_SESSION['nama'] = $nama;
                $_SESSION['foto'] = $foto;
                $user['nama']     = $nama;
                $user['bio']      = $bio;
                $user['foto']     = $foto;
                $success          = 'Profil berhasil diperbarui.';
            }
        }

    } elseif ($aksi === 'ganti_password') {
        $pw_lama  = $_POST['password_lama']  ?? '';
        $pw_baru  = $_POST['password_baru']  ?? '';
        $pw_konfirm = $_POST['password_konfirm'] ?? '';

        if (!password_verify($pw_lama, $user['password'])) {
            $error = 'Password lama tidak sesuai.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($pw_baru !== $pw_konfirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $upd  = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($upd, 'si', $hash, $user_id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);
            $success = 'Password berhasil diubah.';
        }
    }
}

require_once '../includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container" style="max-width:900px;">

    <div style="margin-bottom:2rem;" class="animate">
        <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--text-primary);">
            <i class="fas fa-user-circle" style="color:var(--green);"></i> Profil <span style="color:var(--green);">Saya</span>
        </h1>
        <div class="divider"></div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:280px 1fr; gap:2rem; align-items:start;">

        <!-- SIDEBAR PROFIL -->
        <div class="animate">
            <!-- Foto & Info -->
            <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; text-align:center; margin-bottom:1.5rem;">
                <div style="position:relative; display:inline-block; margin-bottom:1rem;">
                    <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($user['foto'] ?? 'default.png') ?>"
                         id="foto-preview"
                         style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid var(--green-light);"
                         onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                </div>
                <h3 style="font-family:'Playfair Display',serif; font-size:1.1rem; color:var(--text-primary); margin-bottom:4px;">
                    <?= htmlspecialchars($user['nama']) ?>
                </h3>
                <span class="badge badge-<?= $user['role']==='admin' ? 'sulit' : 'mudah' ?>" style="margin-bottom:0.8rem; display:inline-block;">
                    <?= ucfirst($user['role']) ?>
                </span>
                <p style="font-size:0.85rem; color:var(--text-muted); line-height:1.6;">
                    <?= $user['bio'] ? htmlspecialchars($user['bio']) : 'Belum ada bio.' ?>
                </p>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:0.8rem;">
                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                </div>
                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;">
                    <i class="fas fa-calendar"></i> Bergabung <?= date('M Y', strtotime($user['created_at'])) ?>
                </div>
            </div>

            <!-- Statistik -->
            <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem;">
                <h4 style="font-size:0.875rem; font-weight:600; color:var(--text-primary); margin-bottom:1rem;">Statistik</h4>
                <?php
                $stat_items = [
                    ['label'=>'Resep Dibuat', 'value'=>$stats['total_resep'], 'icon'=>'fa-utensils'],
                    ['label'=>'Total Views',  'value'=>number_format($stats['total_views'] ?? 0), 'icon'=>'fa-eye'],
                    ['label'=>'Favorit',      'value'=>$stats['total_favorit'], 'icon'=>'fa-heart'],
                    ['label'=>'Komentar',     'value'=>$stats['total_komentar'], 'icon'=>'fa-comment'],
                ];
                foreach ($stat_items as $s):
                ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-size:0.85rem; color:var(--text-secondary);">
                        <i class="fas <?= $s['icon'] ?>" style="color:var(--green); width:16px;"></i>
                        <?= $s['label'] ?>
                    </span>
                    <strong style="font-size:0.9rem; color:var(--text-primary);"><?= $s['value'] ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- FORM -->
        <div>
            <!-- Edit Profil -->
            <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate animate-delay-1">
                <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin-bottom:1.5rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                    <i class="fas fa-edit" style="color:var(--green);"></i> Edit Profil
                </h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="update_profil">

                    <div class="form-group">
                        <label class="form-label">Foto Profil</label>
                        <div style="display:flex; align-items:center; gap:1rem;">
                            <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($user['foto'] ?? 'default.png') ?>"
                                 id="preview-img"
                                 style="width:60px; height:60px; border-radius:50%; object-fit:cover; border:2px solid var(--green-light);"
                                 onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                            <div style="flex:1;">
                                <input type="file" name="foto" class="form-control" accept="image/*"
                                       onchange="previewFoto(this)">
                                <small style="color:var(--text-muted); font-size:0.75rem;">JPG/PNG/WEBP, maks 2MB</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control"
                               value="<?= htmlspecialchars($user['email']) ?>" disabled
                               style="opacity:0.6; cursor:not-allowed;">
                        <small style="color:var(--text-muted); font-size:0.75rem;">Email tidak dapat diubah</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3"
                                  placeholder="Ceritakan sedikit tentang dirimu..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-gold">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Ganti Password -->
            <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem;" class="animate animate-delay-2">
                <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin-bottom:1.5rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                    <i class="fas fa-lock" style="color:var(--green);"></i> Ganti Password
                </h3>
                <form method="POST">
                    <input type="hidden" name="aksi" value="ganti_password">
                    <div class="form-group">
                        <label class="form-label">Password Lama *</label>
                        <input type="password" name="password_lama" class="form-control"
                               placeholder="Masukkan password lama" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Baru *</label>
                        <input type="password" name="password_baru" class="form-control"
                               placeholder="Minimal 6 karakter" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru *</label>
                        <input type="password" name="password_konfirm" class="form-control"
                               placeholder="Ulangi password baru" required>
                    </div>
                    <button type="submit" class="btn btn-outline">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </form>
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
            document.getElementById('foto-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
