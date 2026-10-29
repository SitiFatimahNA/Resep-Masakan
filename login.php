<?php
session_start();
require_once 'config/koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: '.($_SESSION['role']==='admin'?'/resep-masakan/admin/dashboard.php':'/resep-masakan/home.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT id,nama,email,password,role,foto FROM users WHERE email=?");
        mysqli_stmt_bind_param($stmt,'s',$email);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['foto']    = $user['foto'];
            header('Location: '.($user['role']==='admin'?'/resep-masakan/admin/dashboard.php':'/resep-masakan/home.php'));
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — DapurIn</title>
    <link rel="stylesheet" href="/resep-masakan/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;overflow:hidden;}

        /* KIRI — Panel dekorasi */
        .auth-left{
            width:45%;flex-shrink:0;
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
        .auth-left-blob{
            position:absolute;
            border-radius:50%;
            background:radial-gradient(circle,rgba(82,183,136,0.15) 0%,transparent 70%);
            pointer-events:none;
        }

        .auth-brand{
            display:flex;align-items:center;gap:12px;position:relative;z-index:1;
        }
        .auth-brand-icon{
            width:42px;height:42px;
            background:linear-gradient(135deg,#40916c,#52b788);
            border-radius:12px;
            display:flex;align-items:center;justify-content:center;
            font-size:1.1rem;color:#fff;
            box-shadow:0 4px 14px rgba(64,145,108,0.5);
        }
        .auth-brand-text{
            font-family:'Playfair Display',serif;
            font-size:1.3rem;font-weight:700;color:#fff;
        }
        .auth-brand-text span{color:#95d5b2;}

        .auth-left-body{position:relative;z-index:1;}
        .auth-left-body h2{
            font-family:'Playfair Display',serif;
            font-size:2.2rem;font-weight:700;
            color:#fff;line-height:1.2;
            margin-bottom:1rem;letter-spacing:-0.5px;
        }
        .auth-left-body h2 span{
            background:linear-gradient(135deg,#52b788,#95d5b2);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
        }
        .auth-left-body p{color:rgba(255,255,255,0.55);font-size:0.9rem;line-height:1.8;max-width:320px;}

        /* Feature list */
        .auth-features{display:flex;flex-direction:column;gap:12px;margin-top:2rem;}
        .auth-feature{
            display:flex;align-items:center;gap:12px;
            background:rgba(255,255,255,0.06);
            border:1px solid rgba(255,255,255,0.08);
            border-radius:12px;padding:12px 16px;
        }
        .auth-feature-icon{
            width:34px;height:34px;
            background:rgba(82,183,136,0.2);
            border-radius:10px;
            display:flex;align-items:center;justify-content:center;
            font-size:0.9rem;color:#52b788;flex-shrink:0;
        }
        .auth-feature span{font-size:0.82rem;color:rgba(255,255,255,0.7);font-weight:500;}

        .auth-left-footer{position:relative;z-index:1;}
        .auth-left-footer p{font-size:0.75rem;color:rgba(255,255,255,0.3);}

        /* KANAN — Form */
        .auth-right{
            flex:1;
            background:#f4f7f4;
            display:flex;align-items:center;justify-content:center;
            padding:3rem 2rem;
            overflow-y:auto;
        }

        .auth-form-wrap{width:100%;max-width:400px;}

        .auth-form-title{
            font-family:'Playfair Display',serif;
            font-size:1.7rem;font-weight:700;
            color:#1a2e1a;margin-bottom:0.4rem;letter-spacing:-0.5px;
        }
        .auth-form-sub{font-size:0.85rem;color:#8a9e87;margin-bottom:2rem;}

        .auth-form-group{margin-bottom:1.1rem;}
        .auth-label{
            display:block;font-size:0.75rem;font-weight:700;
            color:#4a6741;text-transform:uppercase;letter-spacing:0.8px;
            margin-bottom:6px;
        }
        .auth-input-wrap{position:relative;}
        .auth-input-icon{
            position:absolute;left:14px;top:50%;transform:translateY(-50%);
            color:#8a9e87;font-size:0.85rem;pointer-events:none;
        }
        .auth-input{
            width:100%;padding:11px 14px 11px 40px;
            background:#fff;border:1.5px solid #d4e6d4;border-radius:12px;
            font-size:0.875rem;font-family:'Poppins',sans-serif;
            color:#1a2e1a;outline:none;transition:all 0.2s;
        }
        .auth-input:focus{border-color:#40916c;box-shadow:0 0 0 3px rgba(64,145,108,0.1);}
        .auth-input::placeholder{color:#b0c4b0;}
        .toggle-pw{
            position:absolute;right:14px;top:50%;transform:translateY(-50%);
            background:none;border:none;color:#8a9e87;cursor:pointer;
            font-size:0.9rem;transition:color 0.2s;
        }
        .toggle-pw:hover{color:#40916c;}

        .auth-error{
            display:flex;align-items:center;gap:8px;
            background:rgba(231,76,60,0.08);border:1.5px solid rgba(231,76,60,0.2);
            border-radius:10px;padding:10px 14px;
            font-size:0.82rem;color:#c0392b;margin-bottom:1.2rem;
        }
        .auth-success{
            display:flex;align-items:center;gap:8px;
            background:rgba(45,106,79,0.08);border:1.5px solid rgba(45,106,79,0.2);
            border-radius:10px;padding:10px 14px;
            font-size:0.82rem;color:#2d6a4f;margin-bottom:1.2rem;
        }

        .auth-btn{
            width:100%;padding:12px;
            background:linear-gradient(135deg,#2d6a4f,#40916c);
            color:#fff;border:none;border-radius:12px;
            font-size:0.9rem;font-weight:600;cursor:pointer;
            font-family:'Poppins',sans-serif;
            box-shadow:0 4px 14px rgba(45,106,79,0.3);
            transition:all 0.3s;margin-top:0.5rem;
            display:flex;align-items:center;justify-content:center;gap:8px;
        }
        .auth-btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(45,106,79,0.4);}

        .auth-divider{
            display:flex;align-items:center;gap:12px;
            margin:1.4rem 0;color:#b0c4b0;font-size:0.78rem;
        }
        .auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:#d4e6d4;}

        .auth-link{
            text-align:center;font-size:0.85rem;color:#8a9e87;margin-top:1.2rem;
        }
        .auth-link a{color:#2d6a4f;font-weight:600;text-decoration:none;}
        .auth-link a:hover{text-decoration:underline;}

        @media(max-width:768px){
            .auth-left{display:none;}
            .auth-right{padding:2rem 1.5rem;}
        }

        @keyframes fadeInUp{
            from{opacity:0;transform:translateY(20px);}
            to{opacity:1;transform:translateY(0);}
        }
        .auth-form-wrap{animation:fadeInUp 0.5s ease;}
    </style>
</head>
<body>

<!-- KIRI -->
<div class="auth-left">
    <div class="auth-left-blob" style="top:-150px;right:-150px;width:400px;height:400px;"></div>
    <div class="auth-left-blob" style="bottom:-100px;left:-100px;width:300px;height:300px;"></div>

    <div class="auth-brand">
        <div class="auth-brand-icon"><i class="fas fa-utensils"></i></div>
        <div class="auth-brand-text">Dapur<span>In</span></div>
    </div>

    <div class="auth-left-body">
        <h2>Selamat<br>Datang <span>Kembali!</span></h2>
        <p>Masuk dan temukan ribuan resep masakan lezat dari seluruh Indonesia.</p>

        <div class="auth-features">
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-book-open"></i></div>
                <span>Ribuan resep dari berbagai kategori</span>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-heart"></i></div>
                <span>Simpan resep favoritmu</span>
            </div>
            <div class="auth-feature">
                <div class="auth-feature-icon"><i class="fas fa-star"></i></div>
                <span>Beri rating & komentar resep</span>
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
        <h1 class="auth-form-title">Masuk ke Akun</h1>
        <p class="auth-form-sub">Belum punya akun? <a href="/resep-masakan/register.php" style="color:#2d6a4f;font-weight:600;">Daftar sekarang</a></p>

        <?php if ($error): ?>
        <div class="auth-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
        <div class="auth-success"><i class="fas fa-check-circle"></i> Registrasi berhasil! Silakan masuk.</div>
        <?php endif; ?>

        <form method="POST">
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
                    <input type="password" name="password" id="password" class="auth-input"
                           placeholder="Masukkan password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw('password','eye1')">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-btn">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>

        <div class="auth-divider">atau</div>

        <div class="auth-link">
            Belum punya akun? <a href="/resep-masakan/register.php">Daftar Gratis</a>
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
</script>
</body>
</html>
