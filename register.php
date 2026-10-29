<?php
session_start();
require_once 'config/koneksi.php';

if (isset($_SESSION['user_id'])) { header('Location: /resep-masakan/home.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $password= $_POST['password'] ?? '';
    $konfirm = $_POST['konfirm_password'] ?? '';

    if (empty($nama)||empty($email)||empty($password)||empty($konfirm)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($nama) < 3) {
        $error = 'Nama minimal 3 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt,'s',$email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email sudah terdaftar.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2  = mysqli_prepare($koneksi, "INSERT INTO users (nama,email,password,role) VALUES (?,?,?,'user')");
            mysqli_stmt_bind_param($stmt2,'sss',$nama,$email,$hashed);
            if (mysqli_stmt_execute($stmt2)) {
                mysqli_stmt_close($stmt2);
                header('Location: /resep-masakan/login.php?registered=1'); exit;
            } else {
                $error = 'Terjadi kesalahan, coba lagi.';
                mysqli_stmt_close($stmt2);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — DapurIn</title>
    <link rel="stylesheet" href="/resep-masakan/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;overflow:hidden;}

        .auth-left{
            width:40%;flex-shrink:0;
            background:linear-gradient(160deg,#0a2e1a 0%,#1a4731 50%,#2d6a4f 100%);
            position:relative;overflow:hidden;
            display:flex;flex-direction:column;justify-content:space-between;
            padding:3rem;
        }
        .auth-left::before{
            content:'';position:absolute;inset:0;
            background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%2352b788' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E") repeat;
            pointer-events:none;
        }
        .blob{position:absolute;border-radius:50%;background:radial-gradient(circle,rgba(82,183,136,0.15) 0%,transparent 70%);pointer-events:none;}

        .auth-brand{display:flex;align-items:center;gap:12px;position:relative;z-index:1;}
        .auth-brand-icon{width:42px;height:42px;background:linear-gradient(135deg,#40916c,#52b788);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;box-shadow:0 4px 14px rgba(64,145,108,0.5);}
        .auth-brand-text{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:700;color:#fff;}
        .auth-brand-text span{color:#95d5b2;}

        .auth-left-body{position:relative;z-index:1;}
        .auth-left-body h2{font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;color:#fff;line-height:1.2;margin-bottom:1rem;letter-spacing:-0.5px;}
        .auth-left-body h2 span{background:linear-gradient(135deg,#52b788,#95d5b2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .auth-left-body p{color:rgba(255,255,255,0.55);font-size:0.875rem;line-height:1.8;}

        /* Steps visual */
        .auth-steps{display:flex;flex-direction:column;gap:16px;margin-top:2rem;}
        .auth-step{display:flex;align-items:flex-start;gap:14px;}
        .auth-step-num{width:28px;height:28px;background:linear-gradient(135deg,#40916c,#52b788);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.72rem;font-weight:700;color:#fff;flex-shrink:0;box-shadow:0 3px 10px rgba(64,145,108,0.4);}
        .auth-step-info strong{display:block;font-size:0.82rem;font-weight:600;color:#fff;margin-bottom:2px;}
        .auth-step-info span{font-size:0.75rem;color:rgba(255,255,255,0.45);}

        .auth-left-footer{position:relative;z-index:1;}
        .auth-left-footer p{font-size:0.75rem;color:rgba(255,255,255,0.3);}

        /* KANAN */
        .auth-right{flex:1;background:#f4f7f4;display:flex;align-items:center;justify-content:center;padding:2rem;overflow-y:auto;}
        .auth-form-wrap{width:100%;max-width:420px;animation:fadeInUp 0.5s ease;}

        .auth-form-title{font-family:'Playfair Display',serif;font-size:1.7rem;font-weight:700;color:#1a2e1a;margin-bottom:0.4rem;letter-spacing:-0.5px;}
        .auth-form-sub{font-size:0.85rem;color:#8a9e87;margin-bottom:1.8rem;}

        .auth-form-group{margin-bottom:1rem;}
        .auth-label{display:block;font-size:0.72rem;font-weight:700;color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px;}
        .auth-input-wrap{position:relative;}
        .auth-input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8a9e87;font-size:0.82rem;pointer-events:none;}
        .auth-input{width:100%;padding:10px 14px 10px 38px;background:#fff;border:1.5px solid #d4e6d4;border-radius:12px;font-size:0.875rem;font-family:'Poppins',sans-serif;color:#1a2e1a;outline:none;transition:all 0.2s;}
        .auth-input:focus{border-color:#40916c;box-shadow:0 0 0 3px rgba(64,145,108,0.1);}
        .auth-input::placeholder{color:#b0c4b0;}
        .toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#8a9e87;cursor:pointer;font-size:0.88rem;transition:color 0.2s;}
        .toggle-pw:hover{color:#40916c;}

        /* Strength bar */
        .strength-wrap{margin-top:6px;}
        .strength-bar-bg{height:4px;background:#e4ede6;border-radius:2px;overflow:hidden;}
        .strength-bar{height:100%;border-radius:2px;transition:width 0.3s,background 0.3s;width:0;}
        .strength-label{font-size:0.72rem;margin-top:3px;}

        .match-text{font-size:0.72rem;margin-top:3px;}

        .auth-error{display:flex;align-items:center;gap:8px;background:rgba(231,76,60,0.08);border:1.5px solid rgba(231,76,60,0.2);border-radius:10px;padding:10px 14px;font-size:0.82rem;color:#c0392b;margin-bottom:1.2rem;}

        .auth-btn{width:100%;padding:12px;background:linear-gradient(135deg,#2d6a4f,#40916c);color:#fff;border:none;border-radius:12px;font-size:0.9rem;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;box-shadow:0 4px 14px rgba(45,106,79,0.3);transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:0.5rem;}
        .auth-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(45,106,79,0.4);}

        .auth-link{text-align:center;font-size:0.85rem;color:#8a9e87;margin-top:1rem;}
        .auth-link a{color:#2d6a4f;font-weight:600;text-decoration:none;}
        .auth-link a:hover{text-decoration:underline;}

        @media(max-width:768px){.auth-left{display:none;}.auth-right{padding:2rem 1.5rem;}}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
    </style>
</head>
<body>

<!-- KIRI -->
<div class="auth-left">
    <div class="blob" style="top:-150px;right:-150px;width:400px;height:400px;"></div>
    <div class="blob" style="bottom:-100px;left:-100px;width:300px;height:300px;"></div>

    <div class="auth-brand">
        <div class="auth-brand-icon"><i class="fas fa-utensils"></i></div>
        <div class="auth-brand-text">Dapur<span>In</span></div>
    </div>

    <div class="auth-left-body">
        <h2>Bergabung<br>Bersama <span>Kami!</span></h2>
        <p>Daftar gratis dan mulai berbagi resep masakan terbaikmu kepada jutaan orang.</p>

        <div class="auth-steps">
            <div class="auth-step">
                <div class="auth-step-num">1</div>
                <div class="auth-step-info">
                    <strong>Buat Akun Gratis</strong>
                    <span>Daftar dengan email valid kamu</span>
                </div>
            </div>
            <div class="auth-step">
                <div class="auth-step-num">2</div>
                <div class="auth-step-info">
                    <strong>Tambahkan Resep</strong>
                    <span>Upload foto & isi detail resepmu</span>
                </div>
            </div>
            <div class="auth-step">
                <div class="auth-step-num">3</div>
                <div class="auth-step-info">
                    <strong>Bagikan ke Dunia</strong>
                    <span>Publikasikan dan dapatkan feedback</span>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-left-footer">
        <p>© <?= date('Y') ?> DapurIn. Semua hak dilindungi.</p>
    </div>
</div>

<!-- KANAN -->
<div class="auth-right">
    <div class="auth-form-wrap">
        <h1 class="auth-form-title">Buat Akun Baru</h1>
        <p class="auth-form-sub">Sudah punya akun? <a href="/resep-masakan/login.php">Masuk di sini</a></p>

        <?php if ($error): ?>
        <div class="auth-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="form-reg">
            <div class="auth-form-group">
                <label class="auth-label">Nama Lengkap</label>
                <div class="auth-input-wrap">
                    <i class="fas fa-user auth-input-icon"></i>
                    <input type="text" name="nama" class="auth-input"
                           placeholder="Nama lengkap kamu"
                           value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                           minlength="3" required>
                </div>
            </div>

            <div class="auth-form-group">
                <label class="auth-label">Email</label>
                <div class="auth-input-wrap">
                    <i class="fas fa-envelope auth-input-icon"></i>
                    <input type="email" name="email" class="auth-input"
                           placeholder="nama@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="auth-form-group">
                <label class="auth-label">Password</label>
                <div class="auth-input-wrap">
                    <i class="fas fa-lock auth-input-icon"></i>
                    <input type="password" name="password" id="pw" class="auth-input"
                           placeholder="Minimal 6 karakter" minlength="6" required
                           oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('pw','eye1')">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
                <div class="strength-wrap">
                    <div class="strength-bar-bg"><div class="strength-bar" id="str-bar"></div></div>
                    <div class="strength-label" id="str-text"></div>
                </div>
            </div>

            <div class="auth-form-group">
                <label class="auth-label">Konfirmasi Password</label>
                <div class="auth-input-wrap">
                    <i class="fas fa-lock auth-input-icon"></i>
                    <input type="password" name="konfirm_password" id="konfirm" class="auth-input"
                           placeholder="Ulangi password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('konfirm','eye2')">
                        <i class="fas fa-eye" id="eye2"></i>
                    </button>
                </div>
                <div class="match-text" id="match-text"></div>
            </div>

            <button type="submit" class="auth-btn">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>

        <div class="auth-link">
            Sudah punya akun? <a href="/resep-masakan/login.php">Masuk</a>
        </div>
    </div>
</div>

<script>
function togglePw(id, iconId) {
    const input = document.getElementById(id);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

function checkStrength(val) {
    const bar  = document.getElementById('str-bar');
    const text = document.getElementById('str-text');
    let s = 0;
    if (val.length >= 6)  s++;
    if (val.length >= 10) s++;
    if (/[A-Z]/.test(val)) s++;
    if (/[0-9]/.test(val)) s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    const lvl = [
        {w:'20%',c:'#e74c3c',t:'Sangat lemah'},
        {w:'40%',c:'#e67e22',t:'Lemah'},
        {w:'60%',c:'#f1c40f',t:'Cukup'},
        {w:'80%',c:'#2ecc71',t:'Kuat'},
        {w:'100%',c:'#27ae60',t:'Sangat kuat'},
    ][Math.max(0,s-1)];
    bar.style.width      = val.length ? lvl.w : '0';
    bar.style.background = lvl.c;
    text.style.color     = lvl.c;
    text.textContent     = val.length ? lvl.t : '';
}

document.getElementById('konfirm').addEventListener('input', function() {
    const pw    = document.getElementById('pw').value;
    const match = document.getElementById('match-text');
    if (!this.value) { match.textContent = ''; return; }
    if (this.value === pw) {
        match.textContent = '✓ Password cocok';
        match.style.color = '#27ae60';
    } else {
        match.textContent = '✗ Tidak cocok';
        match.style.color = '#e74c3c';
    }
});
</script>
</body>
</html>
