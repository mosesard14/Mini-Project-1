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
    <link rel="stylesheet" href="../style/pengajuanDonasi.css">
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

                                </div>
                            </div>

                            <!-- Nominal -->
                            <div class="fg">
                                <label>Nominal Donasi <span style="color:var(--red-accent)">*</span></label>
                                <input type="number" name="nominal" min="10000" step="1000" placeholder="Min. Rp 10.000"
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