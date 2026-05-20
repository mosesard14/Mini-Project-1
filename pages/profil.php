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
    <style>
        body {
            background: var(--gray-bg);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .pg {
            max-width: 1000px;
            margin: 0 auto;
            padding: 32px 20px 70px;
            flex: 1;
        }

        .profile-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            animation: fadeInUp .45s ease;
        }

        .profile-banner {
            height: 110px;
            background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-darker) 100%);
            position: relative;
        }

        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -55px;
            margin-bottom: px;
            position: relative;
            z-index: 2;
        }

        .avatar-area {
            margin-bottom: 12px;
        }

        .avatar-wrapper {
            position: relative;
            display: inline-block
        }

        .avatar-circle {
            width: 110px;
            height: 118px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }

        .avatar-circle:hover {
            opacity: .88;
        }

        .avatar-edit {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 28px;
            height: 28px;
            background: var(--green-primary);
            border: 2px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
        }

        #inputAvatar {
            display: none;
        }

        .profile-body {
            padding: 2px 36px 36px;
        }

        .profile-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #1a2319;
            text-align: center;
        }

        .profile-role-badge {
            background: var(--green-light);
            color: var(--green-primary);
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 6px;
            width: fit-content;
            margin-bottom: 10px;
        }

        .profile-name-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Ringkasan donasi */
        .ringkasan-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 24px;
        }

        .rs-box {
            border-radius: 10px;
            padding: 12px 14px;
        }

        .rs-box.green {
            background: var(--green-light);
        }

        .rs-box.yellow {
            background: #fef9c3;
        }

        .rs-box.red {
            background: #fee2e2;
        }

        .rs-lbl {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 3px;
        }

        .rs-box.green .rs-lbl {
            color: var(--green-primary);
        }

        .rs-box.yellow .rs-lbl {
            color: #b45309;
        }

        .rs-box.red .rs-lbl {
            color: #dc2626;
        }

        .rs-val {
            font-size: 14px;
            font-weight: 800;
            color: #1a2319;
        }

        .rs-cnt {
            font-size: 11px;
            color: var(--gray-muted);
        }

        .form-divider {
            border: none;
            border-top: 1.5px solid var(--gray-border);
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .form-sec {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 800;
            color: var(--green-primary);
            margin-bottom: 16px;
            margin-top: 10px;
        }

        .fg {
            margin-bottom: 16px;
        }

        .fg label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .fg input,
        .fg textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: #f9faf9;
            color: #333;
            outline: none;
            transition: var(--transition);
        }

        .fg input:focus,
        .fg textarea:focus {
            border-color: var(--green-primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(45, 138, 82, .12);
        }

        .fg input[readonly] {
            background: #f1f1f1;
            color: #888;
            cursor: not-allowed;
        }

        .fg-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media(max-width:520px) {
            .fg-row {
                grid-template-columns: 1fr;
            }

            .ringkasan-grid {
                grid-template-columns: 1fr;
            }
        }

        .success-msg {
            background: var(--green-light);
            border-left: 4px solid var(--green-primary);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--green-dark);
            margin-bottom: 16px;
        }

        .err-list {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: var(--radius-sm);
            padding: 11px 16px;
            margin-bottom: 14px;
        }

        .err-list li {
            font-size: 12px;
            color: #dc2626;
            font-weight: 600;
            list-style: none;
            margin-bottom: 2px;
        }

        .btn-save {
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
            transition: var(--transition);
            box-shadow: 0 3px 12px rgba(45, 138, 82, .28);
        }

        .btn-save:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Montserrat', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 12px;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #dc2626;
            color: white;
        }
    </style>
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