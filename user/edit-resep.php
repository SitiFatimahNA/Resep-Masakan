<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/login.php');
    exit;
}

$id    = (int)($_GET['id'] ?? 0);
$error = '';

if (!$id) { header('Location: /resep-masakan/user/resep-saya.php'); exit; }

// Ambil data resep — hanya milik sendiri atau admin
$stmt = mysqli_prepare($koneksi, "SELECT * FROM resep WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$resep = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$resep || ($resep['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin')) {
    header('Location: /resep-masakan/user/resep-saya.php');
    exit;
}

// Ambil bahan & langkah
$bahan_list   = mysqli_query($koneksi, "SELECT * FROM bahan_resep WHERE id_resep=$id ORDER BY id ASC");
$langkah_list = mysqli_query($koneksi, "SELECT * FROM langkah_resep WHERE id_resep=$id ORDER BY urutan ASC");
$bahan_arr    = [];
$langkah_arr  = [];
while ($b = mysqli_fetch_assoc($bahan_list))   $bahan_arr[]   = $b;
while ($l = mysqli_fetch_assoc($langkah_list)) $langkah_arr[] = $l;

$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$jenis_list    = mysqli_query($koneksi, "SELECT * FROM jenis_masakan ORDER BY nama_jenis ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul       = trim($_POST['judul'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $id_jenis    = (int)($_POST['id_jenis'] ?? 0);
    $kesulitan   = $_POST['tingkat_kesulitan'] ?? 'mudah';
    $waktu       = (int)($_POST['waktu_masak'] ?? 0);
    $porsi       = (int)($_POST['porsi'] ?? 1);
    $status      = $_POST['status'] ?? 'draft';

    $bahan_nama   = $_POST['bahan_nama']   ?? [];
    $bahan_jumlah = $_POST['bahan_jumlah'] ?? [];
    $bahan_satuan = $_POST['bahan_satuan'] ?? [];
    $langkah_isi  = $_POST['langkah_isi']  ?? [];

    if (!$judul || !$deskripsi || !$id_kategori || !$id_jenis || !$waktu || !$porsi) {
        $error = 'Semua field wajib diisi.';
    } else {
        $thumbnail = $resep['thumbnail'];

        // Update thumbnail jika ada upload baru
        if (!empty($_FILES['thumbnail']['name'])) {
            $ext   = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allow = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allow)) {
                $error = 'Format gambar tidak didukung.';
            } elseif ($_FILES['thumbnail']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 3MB.';
            } else {
                $filename = uniqid('resep_') . '.' . $ext;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/resep/' . $filename)) {
                    // Hapus thumbnail lama jika bukan default
                    if ($resep['thumbnail'] && $resep['thumbnail'] !== 'default-resep.svg') {
                        @unlink('../uploads/resep/' . $resep['thumbnail']);
                    }
                    $thumbnail = $filename;
                }
            }
        }

        if (!$error) {
            // Update resep
            $upd = mysqli_prepare($koneksi,
                "UPDATE resep SET judul=?, thumbnail=?, deskripsi=?, id_kategori=?, id_jenis=?,
                 tingkat_kesulitan=?, waktu_masak=?, porsi=?, status=?, updated_at=NOW()
                 WHERE id=?");
            mysqli_stmt_bind_param($upd, 'sssiiisiii',
                $judul, $thumbnail, $deskripsi, $id_kategori, $id_jenis,
                $kesulitan, $waktu, $porsi, $status, $id);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            // Hapus bahan & langkah lama, insert ulang
            mysqli_query($koneksi, "DELETE FROM bahan_resep WHERE id_resep=$id");
            mysqli_query($koneksi, "DELETE FROM langkah_resep WHERE id_resep=$id");

            foreach ($bahan_nama as $idx => $nama) {
                $nama = trim($nama);
                if (!$nama) continue;
                $jml = trim($bahan_jumlah[$idx] ?? '');
                $sat = trim($bahan_satuan[$idx] ?? '');
                $bs  = mysqli_prepare($koneksi, "INSERT INTO bahan_resep (id_resep,nama_bahan,jumlah,satuan) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($bs, 'isss', $id, $nama, $jml, $sat);
                mysqli_stmt_execute($bs);
                mysqli_stmt_close($bs);
            }

            $urutan = 1;
            foreach ($langkah_isi as $idx => $instruksi) {
                $instruksi = trim($instruksi);
                if (!$instruksi) continue;
                $foto_langkah = null;
                if (!empty($_FILES['langkah_foto']['name'][$idx])) {
                    $ext2 = strtolower(pathinfo($_FILES['langkah_foto']['name'][$idx], PATHINFO_EXTENSION));
                    if (in_array($ext2,['jpg','jpeg','png','webp']) && $_FILES['langkah_foto']['size'][$idx] <= 2*1024*1024) {
                        $fname = uniqid('langkah_') . '.' . $ext2;
                        if (move_uploaded_file($_FILES['langkah_foto']['tmp_name'][$idx], '../uploads/langkah/'.$fname)) {
                            $foto_langkah = $fname;
                        }
                    }
                }
                $ls = mysqli_prepare($koneksi, "INSERT INTO langkah_resep (id_resep,urutan,instruksi,foto_langkah) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($ls, 'iiss', $id, $urutan, $instruksi, $foto_langkah);
                mysqli_stmt_execute($ls);
                mysqli_stmt_close($ls);
                $urutan++;
            }

            header("Location: /resep-masakan/detail-resep.php?slug=" . urlencode($resep['slug']) . "&updated=1");
            exit;
        }
    }
}

$page_title = 'Edit Resep';
require_once '../includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container" style="max-width:860px;">

    <div style="margin-bottom:2rem;" class="animate">
        <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--text-primary);">
            <i class="fas fa-edit" style="color:var(--green);"></i> Edit <span style="color:var(--green);">Resep</span>
        </h1>
        <div class="divider"></div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- INFO DASAR -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate">
            <h3 style="font-size:1rem; font-weight:600; margin-bottom:1.5rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border); color:var(--text-primary);">
                <i class="fas fa-info-circle" style="color:var(--green);"></i> Informasi Dasar
            </h3>

            <div class="form-group">
                <label class="form-label">Judul Resep *</label>
                <input type="text" name="judul" class="form-control"
                       value="<?= htmlspecialchars($resep['judul']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi *</label>
                <textarea name="deskripsi" class="form-control" rows="4"><?= htmlspecialchars($resep['deskripsi']) ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Kategori *</label>
                    <select name="id_kategori" class="form-control" required>
                        <?php while ($k = mysqli_fetch_assoc($kategori_list)): ?>
                        <option value="<?= $k['id'] ?>" <?= $resep['id_kategori'] == $k['id'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis *</label>
                    <select name="id_jenis" class="form-control" required>
                        <?php while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                        <option value="<?= $j['id'] ?>" <?= $resep['id_jenis'] == $j['id'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($j['nama_jenis']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Kesulitan</label>
                    <select name="tingkat_kesulitan" class="form-control">
                        <option value="mudah"  <?= $resep['tingkat_kesulitan']==='mudah'  ? 'selected':'' ?>>Mudah</option>
                        <option value="sedang" <?= $resep['tingkat_kesulitan']==='sedang' ? 'selected':'' ?>>Sedang</option>
                        <option value="sulit"  <?= $resep['tingkat_kesulitan']==='sulit'  ? 'selected':'' ?>>Sulit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Masak (menit) *</label>
                    <input type="number" name="waktu_masak" class="form-control" min="1"
                           value="<?= $resep['waktu_masak'] ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Porsi *</label>
                    <input type="number" name="porsi" class="form-control" min="1"
                           value="<?= $resep['porsi'] ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Ganti Thumbnail</label>
                    <?php if ($resep['thumbnail'] && $resep['thumbnail'] !== 'default-resep.svg'): ?>
                    <div style="margin-bottom:8px;">
                        <img src="/resep-masakan/uploads/resep/<?= htmlspecialchars($resep['thumbnail']) ?>"
                             style="width:100%; height:120px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border);"
                             onerror="this.style.display='none'">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                    <small style="color:var(--text-muted); font-size:0.75rem;">Kosongkan jika tidak ingin mengganti</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="publik" <?= $resep['status']==='publik' ? 'selected':'' ?>>Publik</option>
                        <option value="draft"  <?= $resep['status']==='draft'  ? 'selected':'' ?>>Draft</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- BAHAN -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate animate-delay-1">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin:0;">
                    <i class="fas fa-list-ul" style="color:var(--green);"></i> Bahan-bahan
                </h3>
                <button type="button" onclick="tambahBahan()" class="btn btn-outline btn-sm">
                    <i class="fas fa-plus"></i> Tambah
                </button>
            </div>
            <div style="display:grid; grid-template-columns:2fr 1fr 1fr 40px; gap:8px; margin-bottom:8px;">
                <span style="font-size:0.78rem; color:var(--text-muted);">Nama Bahan</span>
                <span style="font-size:0.78rem; color:var(--text-muted);">Jumlah</span>
                <span style="font-size:0.78rem; color:var(--text-muted);">Satuan</span>
                <span></span>
            </div>
            <div id="bahan-container">
                <?php foreach ($bahan_arr as $b): ?>
                <div class="bahan-row" style="display:grid; grid-template-columns:2fr 1fr 1fr 40px; gap:8px; margin-bottom:8px;">
                    <input type="text" name="bahan_nama[]" class="form-control" value="<?= htmlspecialchars($b['nama_bahan']) ?>">
                    <input type="text" name="bahan_jumlah[]" class="form-control" value="<?= htmlspecialchars($b['jumlah']) ?>">
                    <input type="text" name="bahan_satuan[]" class="form-control" value="<?= htmlspecialchars($b['satuan'] ?? '') ?>">
                    <button type="button" onclick="hapusBaris(this)" style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;margin-top:2px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- LANGKAH -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate animate-delay-2">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin:0;">
                    <i class="fas fa-list-ol" style="color:var(--green);"></i> Cara Memasak
                </h3>
                <button type="button" onclick="tambahLangkah()" class="btn btn-outline btn-sm">
                    <i class="fas fa-plus"></i> Tambah
                </button>
            </div>
            <div id="langkah-container">
                <?php foreach ($langkah_arr as $idx => $l): ?>
                <div class="langkah-row" style="display:flex; gap:12px; margin-bottom:1rem; align-items:start;">
                    <div style="width:36px;height:36px;background:var(--green);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;flex-shrink:0;margin-top:4px;" class="langkah-num"><?= $l['urutan'] ?></div>
                    <div style="flex:1;display:grid;grid-template-columns:1fr auto;gap:8px;align-items:start;">
                        <div>
                            <textarea name="langkah_isi[]" class="form-control" rows="2"><?= htmlspecialchars($l['instruksi']) ?></textarea>
                            <input type="file" name="langkah_foto[]" accept="image/*" style="margin-top:6px;font-size:0.8rem;">
                            <?php if ($l['foto_langkah']): ?>
                            <small style="color:var(--text-muted);">Foto saat ini: <?= htmlspecialchars($l['foto_langkah']) ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="hapusBaris(this)" style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex; gap:1rem; justify-content:flex-end;" class="animate animate-delay-3">
            <a href="/resep-masakan/detail-resep.php?slug=<?= urlencode($resep['slug']) ?>" class="btn btn-dark btn-lg">
                <i class="fas fa-times"></i> Batal
            </a>
            <button type="submit" class="btn btn-gold btn-lg">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
</div>

<script>
function tambahBahan() {
    const c = document.getElementById('bahan-container');
    const row = document.createElement('div');
    row.className = 'bahan-row';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr 40px;gap:8px;margin-bottom:8px;';
    row.innerHTML = `
        <input type="text" name="bahan_nama[]" class="form-control" placeholder="Nama bahan">
        <input type="text" name="bahan_jumlah[]" class="form-control" placeholder="Jumlah">
        <input type="text" name="bahan_satuan[]" class="form-control" placeholder="Satuan">
        <button type="button" onclick="hapusBaris(this)" style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;margin-top:2px;">
            <i class="fas fa-times"></i>
        </button>`;
    c.appendChild(row);
}

function tambahLangkah() {
    const c   = document.getElementById('langkah-container');
    const num = c.querySelectorAll('.langkah-row').length + 1;
    const row = document.createElement('div');
    row.className = 'langkah-row';
    row.style.cssText = 'display:flex;gap:12px;margin-bottom:1rem;align-items:start;';
    row.innerHTML = `
        <div style="width:36px;height:36px;background:var(--green);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;flex-shrink:0;margin-top:4px;" class="langkah-num">${num}</div>
        <div style="flex:1;display:grid;grid-template-columns:1fr auto;gap:8px;align-items:start;">
            <div>
                <textarea name="langkah_isi[]" class="form-control" rows="2" placeholder="Langkah memasak..."></textarea>
                <input type="file" name="langkah_foto[]" accept="image/*" style="margin-top:6px;font-size:0.8rem;">
            </div>
            <button type="button" onclick="hapusBaris(this)" style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    c.appendChild(row);
}

function hapusBaris(btn) {
    btn.closest('.bahan-row, .langkah-row').remove();
    document.querySelectorAll('#langkah-container .langkah-num').forEach((el,i) => el.textContent = i+1);
}
</script>

<?php require_once '../includes/footer.php'; ?>
