<?php
error_reporting(0);
ini_set('display_errors', 0);
$page_title = 'Kelola Kategori';
require_once dirname(__DIR__) . '/config/koneksi.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Kolom gambar sudah ada di tabel kategori dan jenis_masakan

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$error = $success = '';
$edit_kat = $edit_jenis = null;

function buat_slug_k($str) {
    return preg_replace('/[\s-]+/', '-', preg_replace('/[^a-z0-9\s-]/', '', strtolower(trim($str))));
}

function upload_gambar_kat($file, $old_gambar = null, $subfolder = 'kategori') {
    if (empty($file['name'])) return $old_gambar;
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > 2 * 1024 * 1024) return false;
    $nama = $subfolder . '-' . time() . '-' . rand(100,999) . '.' . $ext;
    $dir  = dirname(__DIR__) . '/uploads/' . $subfolder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $path = $dir . $nama;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        if ($old_gambar && file_exists($dir . $old_gambar)) {
            @unlink($dir . $old_gambar);
        }
        return $nama;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($aksi === 'tambah-kategori') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        if (!$nama) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $gambar = upload_gambar_kat($_FILES['gambar'] ?? []);
            if ($gambar === false) { $error = 'File gambar tidak valid (maks 2MB, format jpg/png/webp).'; }
            else {
                $s = mysqli_prepare($koneksi, "INSERT INTO kategori (nama_kategori, slug, gambar) VALUES (?,?,?)");
                mysqli_stmt_bind_param($s,'sss',$nama,$slug,$gambar);
                if (mysqli_stmt_execute($s)) $success = 'Kategori berhasil ditambahkan.';
                else $error = 'Slug sudah ada, gunakan nama lain.';
                mysqli_stmt_close($s);
            }
        }
    } elseif ($aksi === 'edit-kategori' && $id) {
        $nama = trim($_POST['nama_kategori'] ?? '');
        if (!$nama) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $old  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM kategori WHERE id=$id"));
            $gambar = upload_gambar_kat($_FILES['gambar'] ?? [], $old['gambar'] ?? null);
            if ($gambar === false) { $error = 'File gambar tidak valid.'; }
            else {
                $s = mysqli_prepare($koneksi, "UPDATE kategori SET nama_kategori=?, slug=?, gambar=? WHERE id=?");
                mysqli_stmt_bind_param($s,'sssi',$nama,$slug,$gambar,$id);
                mysqli_stmt_execute($s); mysqli_stmt_close($s);
                $success = 'Kategori berhasil diperbarui.';
            }
        }
    } elseif ($aksi === 'tambah-jenis') {
        $nama = trim($_POST['nama_jenis'] ?? '');
        if (!$nama) { $error = 'Nama jenis wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $gambar = upload_gambar_kat($_FILES['gambar_jenis'] ?? [], null, 'jenis');
            if ($gambar === false) { $error = 'File gambar tidak valid (maks 2MB, format jpg/png/webp).'; }
            else {
                $s = mysqli_prepare($koneksi, "INSERT INTO jenis_masakan (nama_jenis, slug, gambar) VALUES (?,?,?)");
                mysqli_stmt_bind_param($s,'sss',$nama,$slug,$gambar);
                if (mysqli_stmt_execute($s)) $success = 'Jenis masakan berhasil ditambahkan.';
                else $error = 'Slug sudah ada.';
                mysqli_stmt_close($s);
            }
        }
    } elseif ($aksi === 'edit-jenis' && $id) {
        $nama = trim($_POST['nama_jenis'] ?? '');
        if (!$nama) { $error = 'Nama jenis wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $old  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM jenis_masakan WHERE id=$id"));
            $gambar = upload_gambar_kat($_FILES['gambar_jenis'] ?? [], $old['gambar'] ?? null, 'jenis');
            if ($gambar === false) { $error = 'File gambar tidak valid.'; }
            else {
                $s = mysqli_prepare($koneksi, "UPDATE jenis_masakan SET nama_jenis=?, slug=?, gambar=? WHERE id=?");
                mysqli_stmt_bind_param($s,'sssi',$nama,$slug,$gambar,$id);
                mysqli_stmt_execute($s); mysqli_stmt_close($s);
                $success = 'Jenis masakan diperbarui.';
            }
        }
    }
}

if ($aksi === 'hapus-kategori' && $id) {
    $old = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM kategori WHERE id=$id"));
    if ($old && $old['gambar']) @unlink(dirname(__DIR__) . '/uploads/kategori/' . $old['gambar']);
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=0");
    mysqli_query($koneksi, "DELETE FROM resep_kategori WHERE id_kategori=$id");
    mysqli_query($koneksi, "UPDATE resep SET id_kategori=NULL WHERE id_kategori=$id");
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id=$id");
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=1");
    header('Location: /admin/kelola-kategori.php?msg=deleted'); exit;
}
if ($aksi === 'hapus-jenis' && $id) {
    $old = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT gambar FROM jenis_masakan WHERE id=$id"));
    if ($old && $old['gambar']) @unlink(dirname(__DIR__) . '/uploads/jenis/' . $old['gambar']);
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=0");
    mysqli_query($koneksi, "UPDATE resep SET id_jenis=NULL WHERE id_jenis=$id");
    mysqli_query($koneksi, "DELETE FROM jenis_masakan WHERE id=$id");
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS=1");
    header('Location: /admin/kelola-kategori.php?msg=deleted'); exit;
}

if ($aksi === 'edit-form-kategori' && $id)
    $edit_kat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kategori WHERE id=$id"));
if ($aksi === 'edit-form-jenis' && $id)
    $edit_jenis = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM jenis_masakan WHERE id=$id"));

$kategori_list = mysqli_query($koneksi,
    "SELECT k.*, COUNT(r.id) as total_resep FROM kategori k
     LEFT JOIN resep r ON k.id=r.id_kategori GROUP BY k.id ORDER BY k.nama_kategori");
$jenis_list = mysqli_query($koneksi,
    "SELECT j.*, COUNT(r.id) as total_resep FROM jenis_masakan j
     LEFT JOIN resep r ON j.id=r.id_jenis GROUP BY j.id ORDER BY j.nama_jenis");

$kat_arr = [];
while ($k = mysqli_fetch_assoc($kategori_list)) $kat_arr[] = $k;
$jenis_arr = [];
while ($j = mysqli_fetch_assoc($jenis_list)) $jenis_arr[] = $j;

require_once 'includes/admin-header.php';
?>
<style>
@media (max-width: 1024px) {
    .tabel-kategori { display: none !important; }
    .cards-kategori { display: block !important; }
    .kat-grid { grid-template-columns: 1fr !important; }
}
@media (min-width: 1025px) {
    .tabel-kategori { display: block !important; }
    .cards-kategori { display: none !important; }
}
</style>

<div class="page-header"><h1>Kelola <span>Kategori & Jenis</span></h1></div>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil dihapus.</div><?php endif; ?>

<div class="kat-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

    <!-- ===== KATEGORI ===== -->
    <div>
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <h3><i class="fas fa-<?= $edit_kat?'edit':'plus' ?>"></i> <?= $edit_kat?'Edit Kategori':'Tambah Kategori' ?></h3>
                <?php if ($edit_kat): ?><a href="/admin/kelola-kategori.php" class="btn btn-dark btn-sm">Batal</a><?php endif; ?>
            </div>
            <div style="padding:1.2rem;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="<?= $edit_kat?'edit-kategori':'tambah-kategori' ?>">
                    <?php if ($edit_kat): ?><input type="hidden" name="id" value="<?= $edit_kat['id'] ?>"><?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" name="nama_kategori" class="form-control" required
                               value="<?= htmlspecialchars($edit_kat['nama_kategori'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Kategori <?= $edit_kat ? '(kosongkan jika tidak diubah)' : '' ?></label>
                        <?php if ($edit_kat && $edit_kat['gambar']): ?>
                        <div style="margin-bottom:8px;">
                            <img src="/uploads/kategori/<?= htmlspecialchars($edit_kat['gambar']) ?>"
                                 style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border);"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/png,image/webp"
                               <?= $edit_kat ? '' : 'required' ?>>
                        <small style="color:var(--text-3);font-size:0.72rem;">Format: JPG, PNG, WebP. Maks 2MB.</small>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> <?= $edit_kat?'Simpan':'Tambah' ?></button>
                </form>
            </div>
        </div>

        <!-- Desktop -->
        <div class="admin-card tabel-kategori">
            <table class="admin-table">
                <thead><tr><th>Kategori</th><th>Resep</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($kat_arr as $k): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if ($k['gambar']): ?>
                            <img src="/uploads/kategori/<?= htmlspecialchars($k['gambar']) ?>"
                                 style="width:40px;height:36px;object-fit:cover;border-radius:8px;flex-shrink:0;"
                                 onerror="this.src='/assets/images/default-resep.svg'">
                            <?php else: ?>
                            <div style="width:40px;height:36px;background:rgba(45,106,79,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-image" style="color:var(--green-3);font-size:0.8rem;"></i>
                            </div>
                            <?php endif; ?>
                            <span style="font-size:0.85rem;font-weight:600;color:var(--text-1);"><?= htmlspecialchars($k['nama_kategori']) ?></span>
                        </div>
                    </td>
                    <td><span class="badge badge-user"><?= $k['total_resep'] ?> resep</span></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="?aksi=edit-form-kategori&id=<?= $k['id'] ?>" class="btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                            <a href="?aksi=hapus-kategori&id=<?= $k['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="cards-kategori">
            <?php foreach ($kat_arr as $k): ?>
            <div style="background:var(--bg-card);border-radius:14px;border:1.5px solid var(--border);padding:0.9rem;margin-bottom:0.7rem;display:flex;align-items:center;gap:12px;">
                <?php if ($k['gambar']): ?>
                <img src="/uploads/kategori/<?= htmlspecialchars($k['gambar']) ?>"
                     style="width:48px;height:44px;object-fit:cover;border-radius:10px;flex-shrink:0;"
                     onerror="this.src='/assets/images/default-resep.svg'">
                <?php else: ?>
                <div style="width:48px;height:44px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-image" style="color:var(--green-3);font-size:1rem;"></i>
                </div>
                <?php endif; ?>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.875rem;font-weight:700;color:var(--text-1);"><?= htmlspecialchars($k['nama_kategori']) ?></div>
                    <div style="font-size:0.72rem;color:var(--text-3);margin-top:2px;"><?= $k['total_resep'] ?> resep</div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <a href="?aksi=edit-form-kategori&id=<?= $k['id'] ?>" class="btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                    <a href="?aksi=hapus-kategori&id=<?= $k['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ===== JENIS MASAKAN ===== -->
    <div>
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <h3><i class="fas fa-<?= $edit_jenis?'edit':'plus' ?>"></i> <?= $edit_jenis?'Edit Jenis':'Tambah Jenis' ?></h3>
                <?php if ($edit_jenis): ?><a href="/admin/kelola-kategori.php" class="btn btn-dark btn-sm">Batal</a><?php endif; ?>
            </div>
            <div style="padding:1.2rem;">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="<?= $edit_jenis?'edit-jenis':'tambah-jenis' ?>">
                    <?php if ($edit_jenis): ?><input type="hidden" name="id" value="<?= $edit_jenis['id'] ?>"><?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Nama Jenis *</label>
                        <input type="text" name="nama_jenis" class="form-control" required
                               value="<?= htmlspecialchars($edit_jenis['nama_jenis']??'') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Jenis <?= $edit_jenis ? '(kosongkan jika tidak diubah)' : '' ?></label>
                        <?php if ($edit_jenis && !empty($edit_jenis['gambar'])): ?>
                        <div style="margin-bottom:8px;">
                            <img src="/uploads/jenis/<?= htmlspecialchars($edit_jenis['gambar']) ?>"
                                 style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:1.5px solid var(--border);"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="gambar_jenis" class="form-control" accept="image/jpeg,image/png,image/webp"
                               <?= $edit_jenis ? '' : 'required' ?>>
                        <small style="color:var(--text-3);font-size:0.72rem;">Format: JPG, PNG, WebP. Maks 2MB.</small>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> <?= $edit_jenis?'Simpan':'Tambah' ?></button>
                </form>
            </div>
        </div>

        <!-- Desktop -->
        <div class="admin-card tabel-kategori">
            <table class="admin-table">
                <thead><tr><th>Nama Jenis</th><th>Resep</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($jenis_arr as $j): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if (!empty($j['gambar'])): ?>
                            <img src="/uploads/jenis/<?= htmlspecialchars($j['gambar']) ?>"
                                 style="width:40px;height:36px;object-fit:cover;border-radius:8px;flex-shrink:0;"
                                 onerror="this.src='/assets/images/default-resep.svg'">
                            <?php else: ?>
                            <div style="width:40px;height:36px;background:rgba(45,106,79,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas fa-image" style="color:var(--green-3);font-size:0.8rem;"></i>
                            </div>
                            <?php endif; ?>
                            <span style="font-size:0.85rem;font-weight:600;color:var(--text-1);"><?= htmlspecialchars($j['nama_jenis']) ?></span>
                        </div>
                    </td>
                    <td><span class="badge badge-user"><?= $j['total_resep'] ?> resep</span></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="?aksi=edit-form-jenis&id=<?= $j['id'] ?>" class="btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                            <a href="?aksi=hapus-jenis&id=<?= $j['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="cards-kategori">
            <?php foreach ($jenis_arr as $j): ?>
            <div style="background:var(--bg-card);border-radius:14px;border:1.5px solid var(--border);padding:0.9rem;margin-bottom:0.7rem;display:flex;align-items:center;gap:12px;">
                <?php if (!empty($j['gambar'])): ?>
                <img src="/uploads/jenis/<?= htmlspecialchars($j['gambar']) ?>"
                     style="width:48px;height:44px;object-fit:cover;border-radius:10px;flex-shrink:0;"
                     onerror="this.src='/assets/images/default-resep.svg'">
                <?php else: ?>
                <div style="width:48px;height:44px;background:rgba(45,106,79,0.08);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-image" style="color:var(--green-3);font-size:1rem;"></i>
                </div>
                <?php endif; ?>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.875rem;font-weight:700;color:var(--text-1);"><?= htmlspecialchars($j['nama_jenis']) ?></div>
                    <div style="font-size:0.72rem;color:var(--text-3);margin-top:2px;"><?= $j['total_resep'] ?> resep</div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <a href="?aksi=edit-form-jenis&id=<?= $j['id'] ?>" class="btn-action btn-action-edit"><i class="fas fa-edit"></i></a>
                    <a href="?aksi=hapus-jenis&id=<?= $j['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>