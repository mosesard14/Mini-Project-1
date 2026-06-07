<?php
session_start();
include '../koneksi.php';

// Harus login sebagai donatur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=pengajuanDonasi.php&id=' . intval($_GET['id'] ?? 0));
    exit();
}
if (($_SESSION['role'] ?? '') !== 'donatur') {
    header('Location: main.php');
    exit();
}

$user_id  = (int)$_SESSION['user_id'];
$kamp_id  = intval($_GET['id'] ?? 0);
if (!$kamp_id) {
    header('Location: main.php');
    exit();
}

// Ambil data kampanye dari DB
$kamp = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT k.*, u.nama_org FROM kampanye k JOIN users u ON u.id=k.pengelola_id
    WHERE k.id=$kamp_id AND k.status='aktif' AND k.deadline >= CURDATE() LIMIT 1"
));
if (!$kamp) {
    header('Location: main.php');
    exit();
}

// Ambil data donatur dari DB
$user = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT * FROM users WHERE id=$user_id LIMIT 1"
));

$metode_kampanye = json_decode($kamp['metode_json'] ?? '[]', true) ?: [];
$p = pct((int)$kamp['terkumpul'], (int)$kamp['target_dana']);

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal = intval($_POST['nominal'] ?? 0);
    $metode  = trim($_POST['metode']  ?? '');
    $pesan   = trim($_POST['pesan']   ?? '');

    if ($nominal < 10000)   $errors[] = 'Nominal minimal Rp 10.000.';
    if (!$metode)           $errors[] = 'Pilih metode pembayaran.';
    if (!in_array($metode, $metode_kampanye)) $errors[] = 'Metode tidak valid.';

    // Validasi bukti transfer
    $bukti_path = null;
    if (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Bukti transfer wajib diunggah.';
    } else {
        $bukti_path = uploadGambar($_FILES['bukti'], 'bukti');
        if (!$bukti_path) $errors[] = 'Format bukti tidak valid (JPG/PNG/WEBP, maks 5MB).';
    }

    if (empty($errors)) {
        $nom_esc    = (int)$nominal;
        $met_esc    = esc($metode);
        $pesan_esc  = esc($pesan);
        $bukti_esc  = esc($bukti_path ?? '');

        mysqli_query(
            $koneksi,
            "INSERT INTO donasi (kampanye_id,donatur_id,nominal,metode,pesan,bukti_path,status) VALUES ($kamp_id,$user_id,$nom_esc,'$met_esc','$pesan_esc','$bukti_esc','pending')"
        );
        $success = true;
    }
}

$backUrl   = "detailDonasi.php?id=$kamp_id";
$pageTitle = 'Form Donasi';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donasi — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <style>
        body {
            background: var(--gray-bg);
        }

        .pg {
            max-width: 1000px;
            margin: 0 auto;
            padding: 28px 20px 60px;
        }

        .kamp-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            animation: fadeInUp .4s ease;
        }

        .kamp-row {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .kamp-img {
            width: 90px;
            height: 70px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .kamp-judul {
            font-weight: 800;
            font-size: 15px;
            color: #1a2319;
            margin-bottom: 4px;
        }

        .kamp-stats {
            display: flex;
            gap: 24px;
            margin-top: 8px;
        }

        .ks-lbl {
            font-size: 10px;
            font-weight: 700;
            color: var(--gray-muted);
            font-style: italic;
        }

        .ks-val {
            font-size: 14px;
            font-weight: 800;
            color: var(--green-primary);
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 22px;
            align-items: start;
        }

        @media(max-width:780px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        .sc {
            background: white;
            border-radius: var(--radius-md);
            padding: 22px 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            animation: fadeInUp .5s ease;
        }

        .sc-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: var(--green-primary);
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1.5px solid var(--gray-border);
        }

        .fg {
            margin-bottom: 18px;
        }

        .fg>label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--green-primary);
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 6px;
            margin-right: 10px;
        }

        .fg input,
        .fg select,
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
        .fg select:focus,
        .fg textarea:focus {
            border-color: var(--green-primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(45, 138, 82, .10);
        }

        .fg input[type="radio"] {
            width: auto;
            padding: 0;
            background: none;
            border: none;
        }

        .fg textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* anon toggle */
        .anon-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .anon-row input[type="text"] {
            flex: 1;
        }

        .toggle-lbl {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-muted);
            cursor: pointer;
            white-space: nowrap;
        }

        .sw {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 20px;
        }

        .sw input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .sl {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            border-radius: 20px;
            transition: .3s;
        }

        .sl::before {
            position: absolute;
            content: '';
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: .3s;
        }

        input:checked+.sl {
            background: var(--green-primary);
        }

        input:checked+.sl::before {
            transform: translateX(16px);
        }

        /* file drop */
        .file-drop {
            border: 2px dashed var(--gray-border);
            border-radius: var(--radius-sm);
            padding: 22px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray-muted);
            font-size: 13px;
        }

        .file-drop:hover {
            border-color: var(--green-primary);
            color: var(--green-primary);
        }

        .file-drop input {
            display: none;
        }

        /* metode options */
        .metode-opts {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .metode-opt {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f9faf9;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .metode-opt span {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            text-transform: uppercase;
        }

        .metode-opt:hover {
            border-color: var(--green-primary);
        }

        .metode-opt input[type="radio"] {
            accent-color: var(--green-primary);
            flex-shrink: 0;
        }

        .metode-opt label {
            white-space: nowrap;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .metode-opt.selected {
            border-color: var(--green-primary);
            background: var(--green-light);
        }

        /* error */
        .err-list {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .err-list li {
            font-size: 13px;
            color: #dc2626;
            font-weight: 600;
            list-style: none;
            margin-bottom: 3px;
        }

        /* btn submit */
        .btn-donasi {
            width: 100%;
            padding: 14px;
            background: var(--green-primary);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 3px 14px rgba(45, 138, 82, .28);
        }

        .btn-donasi:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        /* user info box */
        .user-box {
            background: var(--green-light);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .user-box img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-box .un {
            font-weight: 700;
            font-size: 14px;
            color: #1a2319;
        }

        .user-box .ue {
            font-size: 12px;
            color: var(--gray-muted);
        }
    </style>
</head>

<body>
    <?php include '_navbar.php'; ?>

    <?php if ($success): ?>
        <!-- SUKSES -->
        <div style="max-width:1000px;margin:60px auto;padding:0 20px;text-align:center;">
            <div style="background:white;border-radius:var(--radius-xl);padding:60px 40px;box-shadow:var(--shadow-lg);max-width:480px;margin:0 auto;animation:fadeInUp .4s ease">
                <div style="font-size:56px;margin-bottom:16px;">🎉</div>
                <h2 style="font-family:'Montserrat',sans-serif;font-size:22px;font-weight:800;color:var(--green-primary);margin-bottom:10px;">Donasi Terkirim!</h2>
                <p style="color:var(--gray-text);font-size:14px;margin-bottom:10px;">Donasi Anda sedang menunggu verifikasi dari penyelenggara. Status: <strong style="color:#b45309;">PENDING</strong></p>
                <p style="color:var(--gray-muted);font-size:13px;margin-bottom:28px;">Dana tidak akan langsung terakumulasi. Penyelenggara akan memverifikasi bukti transfer Anda.</p>
                <a href="detailDonasi.php?id=<?= $kamp_id ?>" class="btn btn-outline" style="margin-right:10px;">Kembali ke Kampanye</a>
                <a href="main.php" class="btn btn-primary">Beranda</a>
            </div>
        </div>
    <?php else: ?>

        <div class="pg">

            <!-- RINGKASAN KAMPANYE -->
            <div class="kamp-card">
                <div class="kamp-row">
                    <img src="../<?= htmlspecialchars($kamp['foto_path']) ?>" class="kamp-img"
                        alt="foto" onerror="this.src='../assets/contoh1.jpg'">
                    <div style="flex:1">
                        <div class="kamp-judul"><?= htmlspecialchars($kamp['judul']) ?></div>
                        <div style="font-size:12px;color:var(--gray-muted);"><?= htmlspecialchars($kamp['nama_org']) ?></div>
                        <div class="kamp-stats">
                            <div>
                                <div class="ks-lbl">Terkumpul</div>
                                <div class="ks-val"><?= rupiah((int)$kamp['terkumpul']) ?></div>
                            </div>
                            <div>
                                <div class="ks-lbl">Target</div>
                                <div class="ks-val" style="color:var(--gray-muted)"><?= rupiah((int)$kamp['target_dana']) ?></div>
                            </div>
                            <div>
                                <div class="ks-lbl">Progress</div>
                                <div class="ks-val"><?= $p ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?= $p ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="two-col">

                <!-- FORM DONASI -->
                <div>
                    <div class="sc">
                        <div class="sc-title">Isi Data Donasi</div>

                        <?php if ($errors): ?>
                            <ul class="err-list">
                                <?php foreach ($errors as $e): ?><li>⚠️ <?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Info donatur -->
                        <div class="user-box">
                            <img src="../<?= htmlspecialchars($user['foto_profil']) ?>" alt="av"
                                onerror="this.src='../assets/avatar1.jpeg'">
                            <div>
                                <div class="un"><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></div>
                                <div class="ue"><?= htmlspecialchars($user['email']) ?></div>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data">

                            <!-- Nama donatur -->
                            <div class="fg">
                                <label>Nama Donatur</label>
                                <div class="anon-row">
                                    <input type="text" id="namaInput" name="nama_donatur"
                                        value="<?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?>">
                                    <label class="toggle-lbl">
                                        Anonim
                                        <label class="sw">
                                            <input type="checkbox" id="anonChk" onchange="toggleAnon(this)">
                                            <span class="sl"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>

                            <!-- Nominal -->
                            <div class="fg">
                                <label>Nominal Donasi <span style="color:var(--red-accent)">*</span></label>
                                <input type="number" name="nominal" min="10000" placeholder="Min. Rp 10.000"
                                    value="<?= htmlspecialchars($_POST['nominal'] ?? '') ?>">
                            </div>

                            <!-- Metode -->
                            <div class="fg">
                                <label>Metode Pembayaran <span style="color:var(--red-accent)">*</span></label>
                                <div class="metode-opts">
                                    <?php foreach ($metode_kampanye as $m): ?>
                                        <div class="metode-opt <?= ($_POST['metode'] ?? '') === $m ? 'selected' : '' ?>"
                                            onclick="selectMetode(this,'<?= $m ?>')">
                                            <input type="radio" name="metode" value="<?= $m ?>"
                                                id="m_<?= $m ?>" <?= ($_POST['metode'] ?? '') === $m ? 'checked' : '' ?>>
                                            <span style="flex:1; font-size:13px; font-weight:600; color:#333; text-transform:uppercase; overflow:hidden; min-width:0;" for="m_<?= $m ?>"><?= $m ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Pesan -->
                            <div class="fg">
                                <label>Pesan Dukungan <span style="color:var(--gray-muted)">(opsional)</span></label>
                                <textarea name="pesan" placeholder="Tulis pesan penyemangat..."><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
                            </div>

                            <!-- Bukti transfer -->
                            <div class="fg">
                                <label>Bukti Transfer <span style="color:var(--red-accent)">*</span></label>
                                <label class="file-drop" for="buktiFile">
                                    <div style="font-size:28px;margin-bottom:6px;">📎</div>
                                    <div id="fileName">Klik untuk unggah bukti transfer</div>
                                    <div style="font-size:11px;margin-top:4px;">JPG, PNG, WEBP — maks 5MB</div>
                                    <input type="file" id="buktiFile" name="bukti"
                                        accept="image/jpeg,image/png,image/webp"
                                        onchange="showFile(this)">
                                </label>
                            </div>

                            <button type="submit" class="btn-donasi">💚 Kirim Donasi</button>
                        </form>
                    </div>
                </div>

                <!-- INFO METODE PEMBAYARAN -->
                <div>
                    <div class="sc">
                        <div class="sc-title">Info Pembayaran</div>
                        <?php if (in_array('QRIS', $metode_kampanye) && $kamp['qris_path']): ?>
                            <div style="margin-bottom:14px;">
                                <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--green-primary);margin-bottom:6px;">QRIS</div>
                                <img src="../<?= htmlspecialchars($kamp['qris_path']) ?>"
                                    style="width:130px;height:130px;border-radius:8px;object-fit:cover;" alt="QRIS">
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('Rekening', $metode_kampanye) && $kamp['no_rekening']): ?>
                            <div style="margin-bottom:12px;background:#f9faf9;border:1.5px solid var(--gray-border);border-radius:var(--radius-sm);padding:12px;">
                                <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--green-primary);margin-bottom:4px;">Rekening Bank</div>
                                <div style="font-size:14px;font-weight:600;"><?= htmlspecialchars($kamp['no_rekening']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('E-Wallet', $metode_kampanye) && $kamp['no_ewallet']): ?>
                            <div style="margin-bottom:12px;background:#f9faf9;border:1.5px solid var(--gray-border);border-radius:var(--radius-sm);padding:12px;">
                                <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--green-primary);margin-bottom:4px;">E-Wallet</div>
                                <div style="font-size:14px;font-weight:600;"><?= htmlspecialchars($kamp['no_ewallet']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('BTC', $metode_kampanye) && $kamp['no_btc']): ?>
                            <div style="margin-bottom:12px;background:#f9faf9;border:1.5px solid var(--gray-border);border-radius:var(--radius-sm);padding:12px;">
                                <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--green-primary);margin-bottom:4px;">Bitcoin (BTC)</div>
                                <div style="font-size:11px;font-weight:600;word-break:break-all;"><?= htmlspecialchars($kamp['no_btc']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    <?php endif; ?>

    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
    <script>
        function toggleAnon(cb) {
            const inp = document.getElementById('namaInput');
            if (cb.checked) {
                inp.value = 'Anonim';
                inp.disabled = true;
                inp.style.opacity = '.5';
            } else {
                inp.value = '';
                inp.disabled = false;
                inp.style.opacity = '1';
            }
        }

        function showFile(input) {
            if (input.files[0]) document.getElementById('fileName').textContent = '✓ ' + input.files[0].name;
        }

        function selectMetode(el, val) {
            document.querySelectorAll('.metode-opt').forEach(function(o) {
                o.classList.remove('selected');
            });
            el.classList.add('selected');
            el.querySelector('input[type="radio"]').checked = true;
        }
    </script>
</body>

</html>