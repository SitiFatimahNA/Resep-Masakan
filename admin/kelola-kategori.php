<?php
require_once 'includes/admin-header.php';
require_once '../config/koneksi.php';

$page_title = 'Kelola Kategori';
$error = $success = '';
$edit_kat = $edit_jenis = null;

// ===== KATEGORI =====
$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$tipe = $_GET['tipe'] ?? $_POST['tipe'] ?? 'kategori';

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

// Hapus via GET
if ($aksi === 'hapus-kategori' && $id) {
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-kategori.php?msg=deleted&tipe=kategori'); exit;
}
if ($aksi === 'hapus-jenis' && $id) {
    mysqli_query($koneksi, "DELETE FROM jenis_masakan WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-kategori.php?msg=deleted&tipe=jenis'); exit;
}

// Edit load
if ($aksi === 'edit-form-kategori' && $id) {
    $edit_kat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kategori WHERE id=$id"));
}
if ($aksi === 'edit-form-jenis' && $id) {
    $edit_jenis = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM jenis_masakan WHERE id=$id"));
}

$kategori_list = mysqli_query($koneksi,
    "SELECT k.*, COUNT(r.id) as total_resep FROM kategori k
     LEFT JOIN resep r ON k.id=r.id_kategori GROUP BY k.id ORDER BY k.nama_kategori");
$jenis_list    = mysqli_query($koneksi,
    "SELECT j.*, COUNT(r.id) as total_resep FROM jenis_masakan j
     LEFT JOIN resep r ON j.id=r.id_jenis GROUP BY j.id ORDER BY j.nama_jenis");
?>

<div class="page-header"><h1>Kelola <span>Kategori & Jenis</span></h1></div>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Data berhasil dihapus.</div><?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">

    <!-- ===== KATEGORI ===== -->
    <div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:1.5rem;" class="animate">
            <h3 style="font-size:0.95rem; font-weight:600; margin-bottom:1.2rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border); color:var(--text-primary);">
                <i class="fas fa-<?= $edit_kat ? 'edit' : 'plus' ?>" style="color:var(--green);"></i>
                <?= $edit_kat ? 'Edit Kategori' : 'Tambah Kategori' ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="aksi" value="<?= $edit_kat ? 'edit-kategori' : 'tambah-kategori' ?>">
                <?php if ($edit_kat): ?><input type="hidden" name="id" value="<?= $edit_kat['id'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Nama Kategori *</label>
                    <input type="text" name="nama_kategori" class="form-control"
                           value="<?= htmlspecialchars($edit_kat['nama_kategori'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Icon (Font Awesome class)</label>
                    <input type="text" name="icon" class="form-control" placeholder="fa-utensils"
                           value="<?= htmlspecialchars($edit_kat['icon'] ?? '') ?>">
                    <small style="color:var(--text-muted); font-size:0.75rem;">Contoh: fa-drumstick-bite, fa-fish</small>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-gold btn-sm">
                        <i class="fas fa-save"></i> <?= $edit_kat ? 'Simpan' : 'Tambah' ?>
                    </button>
                    <?php if ($edit_kat): ?>
                    <a href="/resep-masakan/admin/kelola-kategori.php" class="btn btn-dark btn-sm">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate">
            <table class="admin-table">
                <thead><tr><th>Nama</th><th>Resep</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php while ($k = mysqli_fetch_assoc($kategori_list)): ?>
                <tr>
                    <td>
                        <i class="fas <?= htmlspecialchars($k['icon'] ?? 'fa-tag') ?>" style="color:var(--green); margin-right:6px;"></i>
                        <?= htmlspecialchars($k['nama_kategori']) ?>
                    </td>
                    <td><span class="badge badge-mudah"><?= $k['total_resep'] ?></span></td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <a href="?aksi=edit-form-kategori&id=<?= $k['id'] ?>" class="btn btn-outline btn-sm" style="padding:4px 8px;"><i class="fas fa-edit"></i></a>
                            <a href="?aksi=hapus-kategori&id=<?= $k['id'] ?>" class="btn btn-danger btn-sm" style="padding:4px 8px;"
                               onclick="return confirm('Yakin hapus kategori ini?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== JENIS MASAKAN ===== -->
    <div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:1.5rem;" class="animate animate-delay-1">
            <h3 style="font-size:0.95rem; font-weight:600; margin-bottom:1.2rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border); color:var(--text-primary);">
                <i class="fas fa-<?= $edit_jenis ? 'edit' : 'plus' ?>" style="color:var(--green);"></i>
                <?= $edit_jenis ? 'Edit Jenis' : 'Tambah Jenis Masakan' ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="aksi" value="<?= $edit_jenis ? 'edit-jenis' : 'tambah-jenis' ?>">
                <?php if ($edit_jenis): ?><input type="hidden" name="id" value="<?= $edit_jenis['id'] ?>"><?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Nama Jenis *</label>
                    <input type="text" name="nama_jenis" class="form-control"
                           value="<?= htmlspecialchars($edit_jenis['nama_jenis'] ?? '') ?>" required>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn btn-gold btn-sm">
                        <i class="fas fa-save"></i> <?= $edit_jenis ? 'Simpan' : 'Tambah' ?>
                    </button>
                    <?php if ($edit_jenis): ?>
                    <a href="/resep-masakan/admin/kelola-kategori.php" class="btn btn-dark btn-sm">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden;" class="animate animate-delay-1">
            <table class="admin-table">
                <thead><tr><th>Nama Jenis</th><th>Resep</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                <tr>
                    <td style="font-size:0.85rem;"><?= htmlspecialchars($j['nama_jenis']) ?></td>
                    <td><span class="badge badge-mudah"><?= $j['total_resep'] ?></span></td>
                    <td>
                        <div style="display:flex; gap:4px;">
                            <a href="?aksi=edit-form-jenis&id=<?= $j['id'] ?>" class="btn btn-outline btn-sm" style="padding:4px 8px;"><i class="fas fa-edit"></i></a>
                            <a href="?aksi=hapus-jenis&id=<?= $j['id'] ?>" class="btn btn-danger btn-sm" style="padding:4px 8px;"
                               onclick="return confirm('Yakin hapus jenis ini?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
