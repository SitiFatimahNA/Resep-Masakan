<?php
$page_title = 'Kelola Kategori';
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';
require_once 'includes/admin-header.php';
?>
<style>
@media (max-width: 768px) {
    .tabel-kategori { display: none !important; }
    .cards-kategori { display: block !important; }
}
@media (min-width: 769px) {
    .tabel-kategori { display: block !important; }
    .cards-kategori { display: none !important; }
}
</style>
<?php
$error = $success = '';
$edit_kat = $edit_jenis = null;

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

function buat_slug_k($str) {
    return preg_replace('/[\s-]+/', '-', preg_replace('/[^a-z0-9\s-]/', '', strtolower(trim($str))));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($aksi === 'tambah-kategori') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-utensils');
        if (!$nama) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $s = mysqli_prepare($koneksi, "INSERT INTO kategori (nama_kategori, slug, icon) VALUES (?,?,?)");
            mysqli_stmt_bind_param($s,'sss',$nama,$slug,$icon);
            if (mysqli_stmt_execute($s)) $success = 'Kategori berhasil ditambahkan.';
            else $error = 'Slug sudah ada, gunakan nama lain.';
            mysqli_stmt_close($s);
        }
    } elseif ($aksi === 'edit-kategori' && $id) {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        if (!$nama) { $error = 'Nama kategori wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $s = mysqli_prepare($koneksi, "UPDATE kategori SET nama_kategori=?, slug=?, icon=? WHERE id=?");
            mysqli_stmt_bind_param($s,'sssi',$nama,$slug,$icon,$id);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $success = 'Kategori berhasil diperbarui.';
        }
    } elseif ($aksi === 'tambah-jenis') {
        $nama = trim($_POST['nama_jenis'] ?? '');
        if (!$nama) { $error = 'Nama jenis wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $s = mysqli_prepare($koneksi, "INSERT INTO jenis_masakan (nama_jenis, slug) VALUES (?,?)");
            mysqli_stmt_bind_param($s,'ss',$nama,$slug);
            if (mysqli_stmt_execute($s)) $success = 'Jenis masakan berhasil ditambahkan.';
            else $error = 'Slug sudah ada.';
            mysqli_stmt_close($s);
        }
    } elseif ($aksi === 'edit-jenis' && $id) {
        $nama = trim($_POST['nama_jenis'] ?? '');
        if (!$nama) { $error = 'Nama jenis wajib diisi.'; }
        else {
            $slug = buat_slug_k($nama);
            $s = mysqli_prepare($koneksi, "UPDATE jenis_masakan SET nama_jenis=?, slug=? WHERE id=?");
            mysqli_stmt_bind_param($s,'ssi',$nama,$slug,$id);
            mysqli_stmt_execute($s); mysqli_stmt_close($s);
            $success = 'Jenis masakan diperbarui.';
        }
    }
}

if ($aksi === 'hapus-kategori' && $id) {
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-kategori.php?msg=deleted'); exit;
}
if ($aksi === 'hapus-jenis' && $id) {
    mysqli_query($koneksi, "DELETE FROM jenis_masakan WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-kategori.php?msg=deleted'); exit;
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

// Simpan ke array
$kat_arr = [];
while ($k = mysqli_fetch_assoc($kategori_list)) $kat_arr[] = $k;
$jenis_arr = [];
while ($j = mysqli_fetch_assoc($jenis_list)) $jenis_arr[] = $j;
?>

<style>
@media (max-width: 768px) {
    .kat-grid { grid-template-columns: 1fr !important; }
}
</style>

<div class="page-header"><h1>Kelola <span>Kategori & Jenis</span></h1></div>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil dihapus.</div><?php endif; ?>

<div class="kat-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

    <!-- ===== KATEGORI ===== -->
    <div>
        <!-- Form -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <h3><i class="fas fa-<?= $edit_kat?'edit':'plus' ?>"></i> <?= $edit_kat?'Edit Kategori':'Tambah Kategori' ?></h3>
                <?php if ($edit_kat): ?><a href="/resep-masakan/admin/kelola-kategori.php" class="btn btn-dark btn-sm">Batal</a><?php endif; ?>
            </div>
            <div style="padding:1.2rem;">
                <form method="POST">
                    <input type="hidden" name="aksi" value="<?= $edit_kat?'edit-kategori':'tambah-kategori' ?>">
                    <?php if ($edit_kat): ?><input type="hidden" name="id" value="<?= $edit_kat['id'] ?>"><?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Nama Kategori *</label>
                        <input type="text" name="nama_kategori" class="form-control" required
                               value="<?= htmlspecialchars($edit_kat['nama_kategori'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <input type="text" name="icon" class="form-control" placeholder="fa-utensils"
                               value="<?= htmlspecialchars($edit_kat['icon'] ?? '') ?>">
                        <small style="color:var(--text-3);font-size:0.72rem;">Contoh: fa-fish, fa-leaf, fa-egg</small>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-save"></i> <?= $edit_kat?'Simpan':'Tambah' ?></button>
                </form>
            </div>
        </div>

        <!-- List Kategori — MOBILE: card, DESKTOP: tabel -->
        <!-- Desktop -->
        <div class="admin-card tabel-kategori">
            <table class="admin-table">
                <thead><tr><th>Nama Kategori</th><th>Resep</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($kat_arr as $k): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:30px;height:30px;background:rgba(45,106,79,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fas <?= htmlspecialchars($k['icon']??'fa-tag') ?>" style="color:var(--green-3);font-size:0.75rem;"></i>
                            </div>
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

        <!-- Mobile cards -->
        <div class="cards-kategori">
            <?php foreach ($kat_arr as $k): ?>
            <div style="background:var(--bg-card);border-radius:14px;border:1.5px solid var(--border);padding:0.9rem;margin-bottom:0.7rem;display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(45,106,79,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas <?= htmlspecialchars($k['icon']??'fa-tag') ?>" style="color:var(--green-3);font-size:0.9rem;"></i>
                </div>
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
        <!-- Form -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div class="admin-card-header">
                <h3><i class="fas fa-<?= $edit_jenis?'edit':'plus' ?>"></i> <?= $edit_jenis?'Edit Jenis':'Tambah Jenis' ?></h3>
                <?php if ($edit_jenis): ?><a href="/resep-masakan/admin/kelola-kategori.php" class="btn btn-dark btn-sm">Batal</a><?php endif; ?>
            </div>
            <div style="padding:1.2rem;">
                <form method="POST">
                    <input type="hidden" name="aksi" value="<?= $edit_jenis?'edit-jenis':'tambah-jenis' ?>">
                    <?php if ($edit_jenis): ?><input type="hidden" name="id" value="<?= $edit_jenis['id'] ?>"><?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Nama Jenis *</label>
                        <input type="text" name="nama_jenis" class="form-control" required
                               value="<?= htmlspecialchars($edit_jenis['nama_jenis']??'') ?>">
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
                    <td style="font-size:0.85rem;font-weight:600;color:var(--text-1);"><?= htmlspecialchars($j['nama_jenis']) ?></td>
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

        <!-- Mobile cards -->
        <div class="cards-kategori">
            <?php foreach ($jenis_arr as $j): ?>
            <div style="background:var(--bg-card);border-radius:14px;border:1.5px solid var(--border);padding:0.9rem;margin-bottom:0.7rem;display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(45,106,79,0.08);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-utensils" style="color:var(--green-3);font-size:0.85rem;"></i>
                </div>
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
