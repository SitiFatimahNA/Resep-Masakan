<?php
session_start();
require_once 'config/koneksi.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: /resep-masakan/index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm_password'] ?? '';

    // Validasi
    if (empty($nama) || empty($email) || empty($password) || empty($konfirm)) {
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
        // Cek email sudah terdaftar
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email sudah terdaftar, gunakan email lain.';
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);

            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Simpan user baru
            $stmt2 = mysqli_prepare($koneksi, 
                "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'user')"
            );
            mysqli_stmt_bind_param($stmt2, 'sss', $nama, $email, $hashed);

            if (mysqli_stmt_execute($stmt2)) {
                mysqli_stmt_close($stmt2);
                header('Location: /resep-masakan/login.php?registered=1');
                exit;
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
    <title>Daftar | Resep Masakan</title>
    <link rel="stylesheet" href="/resep-masakan/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
            padding: 2rem 1rem;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 40% 60%, rgba(201,168,76,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        .auth-container {
            width: 100%;
            max-width: 480px;
            padding: 1rem;
            animation: fadeInUp 0.6s ease;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo .logo-icon {
            font-size: 3rem;
            display: block;
            margin-bottom: 0.5rem;
            animation: float 3s ease-in-out infinite;
        }

        .auth-logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: var(--text-primary);
        }

        .auth-logo h1 span { color: var(--gold); }

        .auth-logo p {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .auth-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .input-group {
            position: relative;
        }

        .input-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .input-group .form-control {
            padding-left: 40px;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.9rem;
            background: none;
            border: none;
            transition: var(--transition);
        }

        .input-group .toggle-password:hover { color: var(--gold); }

        /* Password strength */
        .password-strength {
            margin-top: 6px;
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s ease, background 0.3s ease;
            width: 0%;
        }

        .strength-text {
            font-size: 0.75rem;
            margin-top: 4px;
            color: var(--text-muted);
        }

        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--gold);
            font-weight: 500;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <!-- Logo -->
        <div class="auth-logo">
            <span class="logo-icon">🍳</span>
            <h1>Resep<span>Masakan</span></h1>
            <p>Bergabung dan bagikan resep favoritmu</p>
        </div>

        <div class="auth-title">
            <i class="fas fa-user-plus text-gold"></i> Buat Akun Baru
        </div>

        <!-- Alert -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form Register -->
        <form method="POST" action="" id="form-register">
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="nama" class="form-control"
                           placeholder="Nama lengkap kamu"
                           value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                           minlength="3" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control"
                           placeholder="email@contoh.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Minimal 6 karakter"
                           minlength="6" required oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-password" onclick="togglePass('password','eye1')">
                        <i class="fas fa-eye" id="eye1"></i>
                    </button>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strength-bar"></div>
                </div>
                <div class="strength-text" id="strength-text"></div>
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="konfirm_password" id="konfirm"
                           class="form-control" placeholder="Ulangi password"
                           required>
                    <button type="button" class="toggle-password" onclick="togglePass('konfirm','eye2')">
                        <i class="fas fa-eye" id="eye2"></i>
                    </button>
                </div>
                <div class="strength-text" id="match-text"></div>
            </div>

            <button type="submit" class="btn btn-gold w-100" style="margin-top: 0.5rem;">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>

        <div class="auth-footer" style="margin-top: 1.2rem;">
            Sudah punya akun? <a href="/resep-masakan/login.php">Masuk di sini</a>
        </div>
    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function checkStrength(val) {
    const bar  = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    let strength = 0;

    if (val.length >= 6) strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const levels = [
        { w: '20%', c: '#e74c3c', t: 'Sangat lemah' },
        { w: '40%', c: '#e67e22', t: 'Lemah' },
        { w: '60%', c: '#f1c40f', t: 'Cukup' },
        { w: '80%', c: '#2ecc71', t: 'Kuat' },
        { w: '100%', c: '#27ae60', t: 'Sangat kuat' },
    ];

    const lvl = levels[Math.max(0, strength - 1)];
    bar.style.width      = lvl.w;
    bar.style.background = lvl.c;
    text.style.color     = lvl.c;
    text.textContent     = val.length ? lvl.t : '';
}

// Cek kecocokan password real-time
document.getElementById('konfirm').addEventListener('input', function() {
    const pass  = document.getElementById('password').value;
    const match = document.getElementById('match-text');
    if (this.value === '') {
        match.textContent = '';
    } else if (this.value === pass) {
        match.textContent = '✓ Password cocok';
        match.style.color = '#2ecc71';
    } else {
        match.textContent = '✗ Password tidak cocok';
        match.style.color = '#e74c3c';
    }
});
</script>
</body>
</html>
