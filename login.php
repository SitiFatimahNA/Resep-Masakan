<?php
session_start();
require_once 'config/koneksi.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /resep-masakan/admin/dashboard.php');
    } else {
        header('Location: /resep-masakan/home.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek user dengan prepared statement
        $stmt = mysqli_prepare($koneksi, "SELECT id, nama, email, password, role, foto FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['foto']    = $user['foto'];

            // Redirect sesuai role
            if ($user['role'] === 'admin') {
                header('Location: /resep-masakan/admin/dashboard.php');
            } else {
                header('Location: /resep-masakan/index.php');
            }
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
    <title>Masuk | Resep Masakan</title>
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
        }

        /* Background dekorasi */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at 60% 40%, rgba(201,168,76,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
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

        .auth-logo h1 span {
            color: var(--gold);
        }

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
            transition: var(--transition);
            background: none;
            border: none;
        }

        .input-group .toggle-password:hover {
            color: var(--gold);
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

        .auth-footer a:hover {
            color: var(--gold-light);
        }

        .divider-text {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.2rem 0;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
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
            <p>Platform berbagi resep terlengkap</p>
        </div>

        <div class="auth-title">
            <i class="fas fa-sign-in-alt text-gold"></i> Masuk ke Akun
        </div>

        <!-- Alert Error -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Alert sukses dari register -->
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registrasi berhasil! Silakan masuk.
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="POST" action="">
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
                           class="form-control" placeholder="Masukkan password"
                           required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-gold w-100" style="margin-top: 0.5rem;">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>

        <div class="divider-text">atau</div>

        <div class="auth-footer">
            Belum punya akun? <a href="/resep-masakan/register.php">Daftar sekarang</a>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
