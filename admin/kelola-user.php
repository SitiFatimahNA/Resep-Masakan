<?php
$page_title = 'Kelola User';
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/resep-masakan/config/koneksi.php';

// Proses aksi SEBELUM include header (agar header() redirect bisa jalan)
if (session_status() === PHP_SESSION_NONE) session_start();

$aksi = $_GET['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

// Toggle role
if ($aksi === 'toggle-role' && $id && $id !== (int)$_SESSION['user_id']) {
    $cur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT role FROM users WHERE id=$id"));
    if ($cur) {
        $new = $cur['role'] === 'admin' ? 'user' : 'admin';
        mysqli_query($koneksi, "UPDATE users SET role='$new' WHERE id=$id");
    }
    header('Location: /resep-masakan/admin/kelola-user.php?msg=updated');
    exit;
}

// Hapus user
if ($aksi === 'hapus' && $id && $id !== (int)$_SESSION['user_id']) {
    $u = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT foto FROM users WHERE id=$id"));
    if ($u && $u['foto'] && $u['foto'] !== 'default.png') @unlink('../uploads/profil/' . $u['foto']);
    mysqli_query($koneksi, "DELETE FROM users WHERE id=$id");
    header('Location: /resep-masakan/admin/kelola-user.php?msg=deleted');
    exit;
}

$q        = trim($_GET['q'] ?? '');
$filter   = $_GET['filter'] ?? 'semua';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$where = ['1=1'];
if ($q) $where[] = "(nama LIKE '%".mysqli_real_escape_string($koneksi,$q)."%' OR email LIKE '%".mysqli_real_escape_string($koneksi,$q)."%')";
if ($filter === 'admin') $where[] = "role='admin'";
if ($filter === 'user')  $where[] = "role='user'";
$w = implode(' AND ', $where);

$total      = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) t FROM users WHERE $w"))['t'];
$total_page = ceil($total / $per_page);
$result     = mysqli_query($koneksi,
    "SELECT u.*, (SELECT COUNT(*) FROM resep WHERE user_id=u.id) as total_resep
     FROM users u WHERE $w ORDER BY u.created_at DESC LIMIT $per_page OFFSET $offset");

require_once 'includes/admin-header.php';
?>

<div class="page-header">
    <h1>Kelola <span>User</span></h1>
    <span style="font-size:0.82rem;color:var(--text-3);background:var(--bg);padding:6px 14px;border-radius:20px;border:1px solid var(--border);">
        <?= $total ?> pengguna
    </span>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i>
    <?= $_GET['msg']==='deleted'?'User berhasil dihapus.':'Role user diperbarui.' ?>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:0.82rem;"></i>
            <input type="text" name="q" class="form-control" placeholder="Cari nama atau email..."
                   value="<?= htmlspecialchars($q) ?>" style="padding-left:36px;">
        </div>
        <select name="filter" class="form-control" style="width:140px;" onchange="this.form.submit()">
            <option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua Role</option>
            <option value="admin" <?= $filter==='admin'?'selected':'' ?>>Admin</option>
            <option value="user"  <?= $filter==='user' ?'selected':'' ?>>User</option>
        </select>
        <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-search"></i> Cari</button>
        <?php if ($q || $filter!=='semua'): ?>
        <a href="/resep-masakan/admin/kelola-user.php" class="btn btn-dark btn-sm"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr><th>No</th><th>User</th><th>Email</th><th>Role</th><th>Resep</th><th>Bergabung</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result)===0): ?>
            <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-3);">Tidak ada user ditemukan</td></tr>
        <?php else: ?>
        <?php $no=$offset+1; while ($u = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td style="color:var(--text-3);font-size:0.78rem;font-weight:600;"><?= $no++ ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <img src="/resep-masakan/uploads/profil/<?= htmlspecialchars($u['foto'] ?? 'default.png') ?>"
                             style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--green-pale);flex-shrink:0;"
                             onerror="this.src='/resep-masakan/assets/images/default-avatar.svg'">
                        <div>
                            <div style="font-size:0.85rem;font-weight:600;color:var(--text-1);">
                                <?= htmlspecialchars($u['nama']) ?>
                                <?php if ($u['id']==$_SESSION['user_id']): ?>
                                <span style="font-size:0.65rem;background:rgba(45,106,79,0.1);color:var(--green-2);padding:1px 6px;border-radius:10px;margin-left:4px;">Saya</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="font-size:0.8rem;color:var(--text-3);"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td style="font-size:0.85rem;font-weight:600;color:var(--text-1);"><?= $u['total_resep'] ?></td>
                <td style="font-size:0.78rem;color:var(--text-3);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <a href="?aksi=toggle-role&id=<?= $u['id'] ?>"
                           class="btn-action btn-action-toggle" title="<?= $u['role']==='admin'?'Jadikan User':'Jadikan Admin' ?>"
                           onclick="return confirm('Ubah role user ini?')">
                            <i class="fas fa-user-cog"></i>
                        </a>
                        <a href="?aksi=hapus&id=<?= $u['id'] ?>"
                           class="btn-action btn-action-delete" title="Hapus"
                           onclick="return confirm('Yakin hapus user ini?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <span style="font-size:0.75rem;color:var(--text-3);">—</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($total_page > 1): ?>
<div class="pagination" style="margin-top:1.5rem;">
    <?php
    $base = '/resep-masakan/admin/kelola-user.php?q='.urlencode($q).'&filter='.$filter.'&page=';
    if ($page>1) echo "<a href='{$base}".($page-1)."'><i class='fas fa-chevron-left'></i></a>";
    for ($p=max(1,$page-2);$p<=min($total_page,$page+2);$p++)
        echo "<a href='{$base}{$p}' class='".($p===$page?'active':'')."'>{$p}</a>";
    if ($page<$total_page) echo "<a href='{$base}".($page+1)."'><i class='fas fa-chevron-right'></i></a>";
    ?>
</div>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>
