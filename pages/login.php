<?php
session_start();
include '../koneksi.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'pengelola' ? 'kelola_kampanye.php' : 'main.php'));
    exit();
}

$backUrl = null;
$pageTitle = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($uname && $pass) {
        $uname_esc = esc($uname);
        $row = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT * FROM users WHERE username='$uname_esc' LIMIT 1"
        ));

        if ($row && password_verify($pass, $row['password'])) {
            $_SESSION['user_id']  = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $row['role'];
            $_SESSION['avatar']   = '../' . $row['foto_profil'];
            $_SESSION['nama']     = $row['nama_lengkap'] ?? $row['username'];
            header('Location: ' . ($row['role'] === 'pengelola' ? 'kelola_kampanye.php' : 'main.php'));
            exit();
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .login-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-card {
            display: flex;
            width: 100%;
            max-width: 820px;
            min-height: 460px;
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp .5s ease;
        }

        .login-left {
            background: white;
            flex: 1;
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 34px;
        }

        .logo-row .dot {
            width: 9px;
            height: 9px;
            background: var(--green-primary);
            border-radius: 50%;
        }

        .logo-row span {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 14px;
            color: var(--green-primary);
        }

        .login-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 30px;
            font-weight: 800;
            font-style: italic;
            color: var(--green-primary);
            margin-bottom: 28px;
        }

        .fgl {
            margin-bottom: 18px;
        }

        .fgl label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .fgl input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: #f9faf9;
            outline: none;
            transition: var(--transition);
            color: #333;
        }

        .fgl input:focus {
            border-color: var(--green-primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(45, 138, 82, .12);
        }

        .btn-masuk {
            width: 100%;
            padding: 13px;
            background: var(--green-primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 15px;
            font-style: italic;
            cursor: pointer;
            margin-top: 8px;
            transition: var(--transition);
            box-shadow: 0 3px 12px rgba(45, 138, 82, .30);
        }

        .btn-masuk:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .hint-box {
            background: var(--green-light);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 12px;
            color: var(--green-dark);
            margin-top: 14px;
        }

        .hint-box b {
            font-weight: 800;
        }

        .login-right {
            width: 42%;
            background: linear-gradient(145deg, var(--green-dark) 0%, var(--green-darker) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px 36px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            background: rgba(255, 255, 255, .04);
            border-radius: 50%;
            top: -80px;
            right: -80px;
        }

        .green-chip {
            display: inline-block;
            background: rgba(127, 255, 168, .15);
            border: 1px solid rgba(127, 255, 168, .30);
            color: #7FFFA8;
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 46px;
            font-weight: 900;
            font-style: italic;
            line-height: 1.05;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .welcome-text {
            font-size: 13px;
            line-height: 1.7;
            opacity: .85;
            position: relative;
            z-index: 1;
        }

        @media(max-width:640px) {
            .login-card {
                flex-direction: column;
            }

            .login-right {
                width: 100%;
                min-height: 160px;
            }

            .login-left {
                padding: 36px 24px;
            }

            .welcome-title {
                font-size: 32px;
            }
        }
    </style>
</head>

<body>
    <?php include '_navbar.php'; ?>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-left">
                <div class="logo-row">
                    <div class="dot"></div><span>DonasiKu Platform</span>
                </div>
                <h1 class="login-title">Masuk</h1>
                <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="POST" novalidate>
                    <div class="fgl">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="fgl">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-masuk">Masuk</button>
                </form>
                <div class="hint-box">
                    <b>Akun demo:</b><br>
                    Pengelola: <b>pengelola1</b> / password<br>
                    Donatur: <b>donatur1</b> / password
                </div>
            </div>
            <div class="login-right">
                <div class="green-chip">✦ Trusted Platform</div>
                <h2 class="welcome-title">Selamat<br>Datang.</h2>
                <p class="welcome-text">Bergabunglah dan mulai<br>membuka atau berdonasi<br>untuk kampanye yang bermakna.</p>
            </div>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
</body>

</html>