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


$redirect_raw = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '');
$redirect_id  = intval($_GET['id'] ?? $_POST['redirect_id'] ?? 0);


$redirect = '';
if ($redirect_raw && preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $redirect_raw)) {
    $redirect = $redirect_raw;
}

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


            if ($redirect) {
                $dest = $redirect;
                if ($redirect_id > 0) $dest .= '?id=' . $redirect_id;
                header('Location: ' . $dest);
            } else {
                header('Location: ' . ($row['role'] === 'pengelola' ? 'kelola_kampanye.php' : 'main.php'));
            }
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
    <link rel="stylesheet" href="../style/login.css">
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

                    <?php if ($redirect): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    <?php endif; ?>
                    <?php if ($redirect_id > 0): ?>
                        <input type="hidden" name="redirect_id" value="<?= $redirect_id ?>">
                    <?php endif; ?>
                    <div class="fgl">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Masukkan username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="fgl">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-masuk">Masuk</button>
                </form>

            </div>
            <div class="login-right">
                <div class="green-chip">Trusted Platform</div>
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