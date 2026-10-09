<?php
session_start();
require_once '../config/koneksi.php';

// Harus login
if (!isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/login.php');
    exit;
}

$page_title = 'Tambah Resep';
$error   = '';
$success = '';

// Ambil kategori & jenis
$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$jenis_list    = mysqli_query($koneksi, "SELECT * FROM jenis_masakan ORDER BY nama_jenis ASC");

// Helper: buat slug
function buat_slug($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return $str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul      = trim($_POST['judul'] ?? '');
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $id_kategori= (int)($_POST['id_kategori'] ?? 0);
    $id_jenis   = (int)($_POST['id_jenis'] ?? 0);
    $kesulitan  = $_POST['tingkat_kesulitan'] ?? 'mudah';
    $waktu      = (int)($_POST['waktu_masak'] ?? 0);
    $porsi      = (int)($_POST['porsi'] ?? 1);
    $status     = $_POST['status'] ?? 'draft';

    $bahan_nama   = $_POST['bahan_nama']   ?? [];
    $bahan_jumlah = $_POST['bahan_jumlah'] ?? [];
    $bahan_satuan = $_POST['bahan_satuan'] ?? [];
    $langkah_isi  = $_POST['langkah_isi']  ?? [];

    // Validasi
    if (!$judul || !$deskripsi || !$id_kategori || !$id_jenis || !$waktu || !$porsi) {
        $error = 'Semua field wajib diisi.';
    } elseif (empty($bahan_nama) || count(array_filter($bahan_nama)) === 0) {
        $error = 'Minimal satu bahan harus diisi.';
    } elseif (empty($langkah_isi) || count(array_filter($langkah_isi)) === 0) {
        $error = 'Minimal satu langkah harus diisi.';
    } else {
        // Upload thumbnail
        $thumbnail = 'default-resep.svg';
        if (!empty($_FILES['thumbnail']['name'])) {
            $ext   = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allow = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allow)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['thumbnail']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 3MB.';
            } else {
                $filename  = uniqid('resep_') . '.' . $ext;
                $upload_to = '../uploads/resep/' . $filename;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_to)) {
                    $thumbnail = $filename;
                } else {
                    $error = 'Gagal upload gambar.';
                }
            }
        }

        if (!$error) {
            // Buat slug unik
            $slug_base = buat_slug($judul);
            $slug      = $slug_base;
            $i         = 1;
            while (true) {
                $cek = mysqli_prepare($koneksi, "SELECT id FROM resep WHERE slug = ?");
                mysqli_stmt_bind_param($cek, 's', $slug);
                mysqli_stmt_execute($cek);
                mysqli_stmt_store_result($cek);
                $ada = mysqli_stmt_num_rows($cek) > 0;
                mysqli_stmt_close($cek);
                if (!$ada) break;
                $slug = $slug_base . '-' . $i++;
            }

            // Insert resep
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO resep (user_id, judul, slug, thumbnail, deskripsi, id_kategori, id_jenis, tingkat_kesulitan, waktu_masak, porsi, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'issssiiisii',
                $_SESSION['user_id'], $judul, $slug, $thumbnail, $deskripsi,
                $id_kategori, $id_jenis, $kesulitan, $waktu, $porsi, $status);
            mysqli_stmt_execute($stmt);
            $id_resep = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt);

            // Insert bahan
            foreach ($bahan_nama as $idx => $nama) {
                $nama = trim($nama);
                if (!$nama) continue;
                $jml = trim($bahan_jumlah[$idx] ?? '');
                $sat = trim($bahan_satuan[$idx] ?? '');
                $bs  = mysqli_prepare($koneksi, "INSERT INTO bahan_resep (id_resep, nama_bahan, jumlah, satuan) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($bs, 'isss', $id_resep, $nama, $jml, $sat);
                mysqli_stmt_execute($bs);
                mysqli_stmt_close($bs);
            }

            // Insert langkah
            $urutan = 1;
            foreach ($langkah_isi as $idx => $instruksi) {
                $instruksi = trim($instruksi);
                if (!$instruksi) continue;

                // Upload foto langkah
                $foto_langkah = null;
                if (!empty($_FILES['langkah_foto']['name'][$idx])) {
                    $ext2 = strtolower(pathinfo($_FILES['langkah_foto']['name'][$idx], PATHINFO_EXTENSION));
                    if (in_array($ext2, ['jpg','jpeg','png','webp']) && $_FILES['langkah_foto']['size'][$idx] <= 2*1024*1024) {
                        $fname = uniqid('langkah_') . '.' . $ext2;
                        if (move_uploaded_file($_FILES['langkah_foto']['tmp_name'][$idx], '../uploads/langkah/' . $fname)) {
                            $foto_langkah = $fname;
                        }
                    }
                }

                $ls = mysqli_prepare($koneksi, "INSERT INTO langkah_resep (id_resep, urutan, instruksi, foto_langkah) VALUES (?,?,?,?)");
                mysqli_stmt_bind_param($ls, 'iiss', $id_resep, $urutan, $instruksi, $foto_langkah);
                mysqli_stmt_execute($ls);
                mysqli_stmt_close($ls);
                $urutan++;
            }

            header("Location: /resep-masakan/detail-resep.php?slug=" . urlencode($slug) . "&success=1");
            exit;
        }
    }
}

require_once '../includes/header.php';
?>

<div style="background:#f8faf8; min-height:100vh; padding:2rem 0 4rem;">
<div class="container" style="max-width:860px;">

    <!-- Header -->
    <div style="margin-bottom:2rem;" class="animate">
        <h1 style="font-family:'Playfair Display',serif; font-size:1.8rem; color:var(--text-primary);">
            <i class="fas fa-plus-circle" style="color:var(--green);"></i> Tambah <span style="color:var(--green);">Resep</span> Baru
        </h1>
        <div class="divider"></div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="form-resep">

        <!-- ===== INFO DASAR ===== -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate">
            <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin-bottom:1.5rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                <i class="fas fa-info-circle" style="color:var(--green);"></i> Informasi Dasar
            </h3>

            <div class="form-group">
                <label class="form-label">Judul Resep <span style="color:var(--danger);">*</span></label>
                <input type="text" name="judul" class="form-control"
                       placeholder="Contoh: Ayam Goreng Crispy Bumbu Rempah"
                       value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Deskripsi <span style="color:var(--danger);">*</span></label>
                <textarea name="deskripsi" class="form-control" rows="4"
                          placeholder="Ceritakan sedikit tentang resep ini..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Kategori <span style="color:var(--danger);">*</span></label>
                    <select name="id_kategori" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while ($k = mysqli_fetch_assoc($kategori_list)): ?>
                        <option value="<?= $k['id'] ?>" <?= ($_POST['id_kategori'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis Masakan <span style="color:var(--danger);">*</span></label>
                    <select name="id_jenis" class="form-control" required>
                        <option value="">-- Pilih Jenis --</option>
                        <?php while ($j = mysqli_fetch_assoc($jenis_list)): ?>
                        <option value="<?= $j['id'] ?>" <?= ($_POST['id_jenis'] ?? '') == $j['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($j['nama_jenis']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Tingkat Kesulitan</label>
                    <select name="tingkat_kesulitan" class="form-control">
                        <option value="mudah"  <?= ($_POST['tingkat_kesulitan'] ?? 'mudah') === 'mudah'  ? 'selected' : '' ?>>Mudah</option>
                        <option value="sedang" <?= ($_POST['tingkat_kesulitan'] ?? '') === 'sedang' ? 'selected' : '' ?>>Sedang</option>
                        <option value="sulit"  <?= ($_POST['tingkat_kesulitan'] ?? '') === 'sulit'  ? 'selected' : '' ?>>Sulit</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Waktu Masak (menit) <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="waktu_masak" class="form-control" min="1"
                           placeholder="30" value="<?= htmlspecialchars($_POST['waktu_masak'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Porsi <span style="color:var(--danger);">*</span></label>
                    <input type="number" name="porsi" class="form-control" min="1"
                           placeholder="4" value="<?= htmlspecialchars($_POST['porsi'] ?? '') ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Foto Thumbnail</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/*"
                           onchange="previewThumbnail(this)">
                    <small style="color:var(--text-muted); font-size:0.75rem;">JPG/PNG/WEBP, maks 3MB</small>
                    <div id="preview-thumbnail" style="margin-top:10px; display:none;">
                        <img id="img-preview" style="width:100%; max-height:200px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border);">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Publikasi</label>
                    <select name="status" class="form-control">
                        <option value="publik" <?= ($_POST['status'] ?? '') === 'publik' ? 'selected' : '' ?>>Publik</option>
                        <option value="draft"  <?= ($_POST['status'] ?? 'draft') === 'draft'  ? 'selected' : '' ?>>Draft (Simpan dulu)</option>
                    </select>
                    <small style="color:var(--text-muted); font-size:0.75rem;">Draft tidak tampil di halaman publik</small>
                </div>
            </div>
        </div>

        <!-- ===== BAHAN ===== -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate animate-delay-1">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin:0;">
                    <i class="fas fa-list-ul" style="color:var(--green);"></i> Bahan-bahan
                </h3>
                <button type="button" onclick="tambahBahan()" class="btn btn-outline btn-sm">
                    <i class="fas fa-plus"></i> Tambah Bahan
                </button>
            </div>

            <!-- Header kolom -->
            <div style="display:grid; grid-template-columns:2fr 1fr 1fr 40px; gap:8px; margin-bottom:8px;">
                <span style="font-size:0.78rem; color:var(--text-muted); font-weight:500;">Nama Bahan</span>
                <span style="font-size:0.78rem; color:var(--text-muted); font-weight:500;">Jumlah</span>
                <span style="font-size:0.78rem; color:var(--text-muted); font-weight:500;">Satuan</span>
                <span></span>
            </div>

            <div id="bahan-container">
                <!-- Bahan default -->
                <div class="bahan-row" style="display:grid; grid-template-columns:2fr 1fr 1fr 40px; gap:8px; margin-bottom:8px;">
                    <input type="text" name="bahan_nama[]" class="form-control" placeholder="Contoh: Ayam">
                    <input type="text" name="bahan_jumlah[]" class="form-control" placeholder="500">
                    <input type="text" name="bahan_satuan[]" class="form-control" placeholder="gram">
                    <button type="button" onclick="hapusBaris(this)" style="background:var(--danger); color:#fff; border:none; border-radius:var(--radius-sm); cursor:pointer; width:36px; height:36px; display:flex; align-items:center; justify-content:center; margin-top:2px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== LANGKAH ===== -->
        <div style="background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:2rem; margin-bottom:1.5rem;" class="animate animate-delay-2">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; padding-bottom:0.8rem; border-bottom:1px solid var(--border);">
                <h3 style="font-size:1rem; font-weight:600; color:var(--text-primary); margin:0;">
                    <i class="fas fa-list-ol" style="color:var(--green);"></i> Cara Memasak
                </h3>
                <button type="button" onclick="tambahLangkah()" class="btn btn-outline btn-sm">
                    <i class="fas fa-plus"></i> Tambah Langkah
                </button>
            </div>

            <div id="langkah-container">
                <!-- Langkah default -->
                <div class="langkah-row" style="display:flex; gap:12px; margin-bottom:1rem; align-items:start;">
                    <div style="width:36px; height:36px; background:var(--green); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; flex-shrink:0; margin-top:4px;" class="langkah-num">1</div>
                    <div style="flex:1; display:grid; grid-template-columns:1fr auto; gap:8px; align-items:start;">
                        <div>
                            <textarea name="langkah_isi[]" class="form-control" rows="2" placeholder="Tuliskan langkah memasak..."></textarea>
                            <input type="file" name="langkah_foto[]" accept="image/*" style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);">
                        </div>
                        <button type="button" onclick="hapusBaris(this)" style="background:var(--danger); color:#fff; border:none; border-radius:var(--radius-sm); cursor:pointer; width:36px; height:36px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Submit -->
        <div style="display:flex; gap:1rem; justify-content:flex-end;" class="animate animate-delay-3">
            <a href="/resep-masakan/user/resep-saya.php" class="btn btn-dark btn-lg">
                <i class="fas fa-times"></i> Batal
            </a>
            <button type="submit" name="status" value="draft" class="btn btn-outline btn-lg">
                <i class="fas fa-save"></i> Simpan Draft
            </button>
            <button type="submit" name="status" value="publik" class="btn btn-gold btn-lg">
                <i class="fas fa-paper-plane"></i> Publikasikan
            </button>
        </div>

    </form>
</div>
</div>

<script>
// Preview thumbnail
function previewThumbnail(input) {
    const prev = document.getElementById('preview-thumbnail');
    const img  = document.getElementById('img-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; prev.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// Tambah bahan
function tambahBahan() {
    const container = document.getElementById('bahan-container');
    const row = document.createElement('div');
    row.className = 'bahan-row';
    row.style.cssText = 'display:grid; grid-template-columns:2fr 1fr 1fr 40px; gap:8px; margin-bottom:8px;';
    row.innerHTML = `
        <input type="text" name="bahan_nama[]" class="form-control" placeholder="Nama bahan">
        <input type="text" name="bahan_jumlah[]" class="form-control" placeholder="Jumlah">
        <input type="text" name="bahan_satuan[]" class="form-control" placeholder="Satuan">
        <button type="button" onclick="hapusBaris(this)" style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;margin-top:2px;">
            <i class="fas fa-times"></i>
        </button>`;
    container.appendChild(row);
}

// Tambah langkah
function tambahLangkah() {
    const container = document.getElementById('langkah-container');
    const num = container.querySelectorAll('.langkah-row').length + 1;
    const row = document.createElement('div');
    row.className = 'langkah-row';
    row.style.cssText = 'display:flex; gap:12px; margin-bottom:1rem; align-items:start;';
    row.innerHTML = `
        <div style="width:36px;height:36px;background:var(--green);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;flex-shrink:0;margin-top:4px;" class="langkah-num">${num}</div>
        <div style="flex:1;display:grid;grid-template-columns:1fr auto;gap:8px;align-items:start;">
            <div>
                <textarea name="langkah_isi[]" class="form-control" rows="2" placeholder="Tuliskan langkah memasak..."></textarea>
                <input type="file" name="langkah_foto[]" accept="image/*" style="margin-top:6px;font-size:0.8rem;color:var(--text-muted);">
            </div>
            <button type="button" onclick="hapusBaris(this)" style="background:var(--danger);color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    container.appendChild(row);
}

// Hapus baris
function hapusBaris(btn) {
    const row = btn.closest('.bahan-row, .langkah-row');
    if (row) row.remove();
    // Update nomor langkah
    document.querySelectorAll('#langkah-container .langkah-num').forEach((el, i) => {
        el.textContent = i + 1;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
