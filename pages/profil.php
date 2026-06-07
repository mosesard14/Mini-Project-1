<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'donatur';
$backUrl = ($role === 'pengelola') ? 'kelola_kampanye.php' : 'main.php';
$pageTitle = 'Profil Saya';

// Ambil user dari DB
$user = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id=$user_id LIMIT 1"
));

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email']        ?? '');
    $telepon      = trim($_POST['telepon']       ?? '');
    $nama_org     = trim($_POST['nama_org']      ?? '');
    $alamat       = trim($_POST['alamat']        ?? '');
    $pw_baru      = trim($_POST['pw_baru']       ?? '');
    $pw_konfirm   = trim($_POST['pw_konfirm']    ?? '');

    if (!$email) $errors[] = 'Email wajib diisi.';

    // Cek email unik (kecuali milik sendiri)
    if ($email) {
        $e_esc = esc($email);
        $dup = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT id FROM users WHERE email='$e_esc' AND id != $user_id LIMIT 1"
        ));
        if ($dup) $errors[] = 'Email sudah digunakan akun lain.';
    }

    // Validasi password baru
    if ($pw_baru) {
        if (strlen($pw_baru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        if ($pw_baru !== $pw_konfirm) $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Upload avatar
    $foto_profil = $user['foto_profil'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fp = uploadGambar($_FILES['foto'], 'avatar');
        if ($fp) {
            $foto_profil = $fp;
        } else $errors[] = 'Format foto tidak valid (JPG/PNG/WEBP, maks 5MB).';
    }

    if (empty($errors)) {
        $nl = esc($nama_lengkap);
        $em = esc($email);
        $tel = esc($telepon);
        $no = esc($nama_org);
        $al = esc($alamat);
        $fp = esc($foto_profil);
        $pw_sql = $pw_baru ? ", password='" . esc(password_hash($pw_baru, PASSWORD_BCRYPT)) . "'" : '';

        mysqli_query(
            $koneksi,
            "UPDATE users SET
            nama_lengkap='$nl', email='$em', telepon='$tel',
            nama_org='$no', alamat='$al', foto_profil='$fp'
            $pw_sql
            WHERE id=$user_id"
        );

        // Refresh session
        $_SESSION['username'] = $user['username'];
        $_SESSION['avatar']   = '../' . $foto_profil;
        $_SESSION['nama']     = $nama_lengkap ?: $user['username'];

        // Refresh user data
        $user = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT * FROM users WHERE id=$user_id LIMIT 1"
        ));
        $success = 'Profil berhasil diperbarui.';
    }
}

// Ringkasan donasi (hanya donatur)
$ringkasan = null;
if ($role === 'donatur') {
    $rq = mysqli_query(
        $koneksi,
        "SELECT status,
                COUNT(*) AS jumlah,
                SUM(nominal) AS total
         FROM donasi WHERE donatur_id=$user_id GROUP BY status"
    );
    $ringkasan = [];
    while ($rr = mysqli_fetch_assoc($rq)) $ringkasan[$rr['status']] = $rr;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/profil.css">
</head>

<body>
    <?php include '_navbar.php'; ?>
    <div class="pg">
        <div class="profile-card">
            <div class="profile-banner"></div>
            <div class="profile-header">
                <div class="avatar-wrapper">
                    <div style="position:relative;display:inline-block;">
                        <img src="../<?= htmlspecialchars($user['foto_profil']) ?>"
                            id="avatarPreview" class="avatar-circle"
                            alt="Avatar" onerror="this.src='../assets/avatar1.jpeg'"
                            onclick="document.getElementById('inputAvatar').click()">
                        <div class="avatar-edit" onclick="document.getElementById('inputAvatar').click()">✏️</div>
                    </div>
                    <input type="file" id="inputAvatar" accept="image/*" onchange="previewAvatar(this)">
                </div>
            </div>

            <div class="profile-body">
                <div class="profile-name-row">
                    <div class="profile-name"><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></div>
                    <div class="profile-role-badge"><?= ucfirst($role) ?></div>
                </div>

                <!-- Ringkasan donasi donatur -->
                <?php if ($role === 'donatur' && $ringkasan): ?>
                    <div class="ringkasan-grid">
                        <div class="rs-box green">
                            <div class="rs-lbl">✅ Verified</div>
                            <div class="rs-val"><?= rupiah((int)($ringkasan['verified']['total'] ?? 0)) ?></div>
                            <div class="rs-cnt"><?= $ringkasan['verified']['jumlah'] ?? 0 ?> donasi</div>
                        </div>
                        <div class="rs-box yellow">
                            <div class="rs-lbl">⏳ Pending</div>
                            <div class="rs-val"><?= rupiah((int)($ringkasan['pending']['total'] ?? 0)) ?></div>
                            <div class="rs-cnt"><?= $ringkasan['pending']['jumlah'] ?? 0 ?> donasi</div>
                        </div>
                        <div class="rs-box red">
                            <div class="rs-lbl">❌ Ditolak</div>
                            <div class="rs-val"><?= rupiah((int)($ringkasan['rejected']['total'] ?? 0)) ?></div>
                            <div class="rs-cnt"><?= $ringkasan['rejected']['jumlah'] ?? 0 ?> donasi</div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?><div class="success-msg">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if ($errors): ?>
                    <ul class="err-list"><?php foreach ($errors as $e): ?><li>⚠️ <?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                <?php endif; ?>

                <hr class="form-divider">
                <div class="form-sec">Edit Informasi Profil</div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto" id="fotoHidden" style="display:none">

                    <div class="fg-row">
                        <div class="fg">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap"
                                value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>"
                                placeholder="Nama lengkap">
                        </div>
                        <div class="fg">
                            <label>Username</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                        </div>
                    </div>
                    <div class="fg-row">
                        <div class="fg">
                            <label>Email *</label>
                            <input type="email" name="email"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="fg">
                            <label>No. Telepon</label>
                            <input type="tel" name="telepon"
                                value="<?= htmlspecialchars($user['telepon'] ?? '') ?>"
                                placeholder="08xx-xxxx-xxxx">
                        </div>
                    </div>

                    <?php if ($role === 'pengelola'): ?>
                        <div class="fg">
                            <label>Nama Penyelenggara / Organisasi</label>
                            <input type="text" name="nama_org"
                                value="<?= htmlspecialchars($user['nama_org'] ?? '') ?>"
                                placeholder="Nama lembaga/organisasi">
                        </div>
                        <div class="fg">
                            <label>Alamat</label>
                            <textarea name="alamat" rows="2"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                        </div>
                    <?php endif; ?>

                    <hr class="form-divider">
                    <div class="form-sec">Ubah Password <span style="font-size:11px;color:var(--gray-muted);font-weight:500;">(kosongkan jika tidak ingin mengubah)</span></div>
                    <div class="fg-row">
                        <div class="fg">
                            <label>Password Baru</label>
                            <input type="password" name="pw_baru" placeholder="Min. 6 karakter">
                        </div>
                        <div class="fg">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="pw_konfirm" placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </form>

                <a href="logout.php" class="btn-logout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Keluar dari Akun
                </a>
            </div>
        </div>
    </div>
    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
    <script>
        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const r = new FileReader();
            r.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
                const dt = new DataTransfer();
                dt.items.add(input.files[0]);
                document.getElementById('fotoHidden').files = dt.files;
            };
            r.readAsDataURL(input.files[0]);
        }
    </script>
</body>

</html>