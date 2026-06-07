<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pengelola') {
    header('Location: login.php');
    exit();
}
$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$backUrl  = 'kelola_kampanye.php';
$pageTitle = 'Buka Donasi';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominal   = intval($_POST['nominal'] ?? 0);
    $judul     = trim($_POST['judul']   ?? '');
    $tag       = trim($_POST['tag']     ?? '');
    $cerita    = trim($_POST['cerita']  ?? '');
    $lokasi    = trim($_POST['lokasi']  ?? '');
    $deadline  = trim($_POST['deadline'] ?? '');
    $metode_arr = $_POST['metode'] ?? [];

    // Detail metode
    $no_rek    = trim($_POST['no_rekening'] ?? '');
    $no_ew     = trim($_POST['no_ewallet']  ?? '');
    $no_btc    = trim($_POST['no_btc']      ?? '');

    if ($nominal <= 0)    $errors[] = 'Target nominal harus lebih dari 0.';
    if (!$judul)          $errors[] = 'Judul wajib diisi.';
    if (!$tag)            $errors[] = 'Pilih kategori kampanye.';
    if (!$cerita)         $errors[] = 'Cerita/deskripsi wajib diisi.';
    if (!$lokasi)         $errors[] = 'Lokasi wajib diisi.';
    if (!$deadline)       $errors[] = 'Deadline wajib diisi.';
    if (empty($metode_arr)) $errors[] = 'Pilih minimal 1 metode pencairan.';

    if (in_array('Rekening', $metode_arr) && !$no_rek) $errors[] = 'Nomor rekening wajib diisi jika memilih metode Rekening.';
    if (in_array('E-Wallet', $metode_arr) && !$no_ew)  $errors[] = 'Nomor E-Wallet wajib diisi jika memilih metode E-Wallet.';
    if (in_array('BTC', $metode_arr)      && !$no_btc) $errors[] = 'Alamat BTC wajib diisi jika memilih metode BTC.';

    // Upload foto kampanye
    $foto_path = 'assets/contoh1.jpg';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fp = uploadGambar($_FILES['foto'], 'kampanye');
        if ($fp) $foto_path = $fp;
        else $errors[] = 'Format foto tidak valid (JPG/PNG/WEBP, maks 5MB).';
    }

    // Upload foto QRIS
    $qris_path = null;
    if (in_array('QRIS', $metode_arr)) {
        if (isset($_FILES['qris_foto']) && $_FILES['qris_foto']['error'] === UPLOAD_ERR_OK) {
            $qp = uploadGambar($_FILES['qris_foto'], 'qris');
            if ($qp) $qris_path = $qp;
            else $errors[] = 'Format gambar QRIS tidak valid.';
        } else {
            $errors[] = 'Gambar QRIS wajib diunggah jika memilih metode QRIS.';
        }
    }

    if (empty($errors)) {
        $j_esc   = esc($judul);
        $c_esc   = esc($cerita);
        $t_esc   = esc($tag);
        $l_esc   = esc($lokasi);
        $dl_esc  = esc($deadline);
        $f_esc   = esc($foto_path);
        $m_json  = esc(json_encode($metode_arr));
        $rek_esc = esc($no_rek);
        $ew_esc  = esc($no_ew);
        $btc_esc = esc($no_btc);
        $q_esc   = $qris_path ? "'" . esc($qris_path) . "'" : 'NULL';

        mysqli_query(
            $koneksi,
            "INSERT INTO kampanye (pengelola_id,judul,cerita,kategori,lokasi,target_dana,deadline,
                                foto_path,metode_json,no_rekening,no_ewallet,no_btc,qris_path)
            VALUES ($user_id,'$j_esc','$c_esc','$t_esc','$l_esc',$nominal,'$dl_esc',
                    '$f_esc','$m_json',
                    " . ($no_rek ? "'$rek_esc'" : 'NULL') . ",
                    " . ($no_ew  ? "'$ew_esc'"  : 'NULL') . ",
                    " . ($no_btc ? "'$btc_esc'" : 'NULL') . ",
                    $q_esc)"
        );
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buka Donasi — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/buka_donasi.css">
</head>

<body>
    <?php include '_navbar.php'; ?>

    <!-- FOTO HEADER -->
    <div class="foto-header">
        <img id="previewImg" src="" alt="preview">
        <div class="foto-placeholder" id="fotoPlaceholder">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="8.5" cy="8.5" r="1.5" />
                <polyline points="21 15 16 10 5 21" />
            </svg>
            Klik "Tambah Foto" untuk menambahkan foto kampanye
        </div>
        <button class="btn-foto" type="button" onclick="document.getElementById('inputFoto').click()">Tambah Foto</button>
        <input type="file" id="inputFoto" accept="image/*" onchange="previewFoto(this)">
    </div>

    <?php if ($success): ?>
        <div class="pg" style="text-align:center;padding-top:60px;">
            <div style="background:white;border-radius:var(--radius-xl);padding:50px 40px;box-shadow:var(--shadow-lg);max-width:400px;margin:0 auto;animation:fadeInUp .4s ease">
                <div style="font-size:52px;margin-bottom:14px;">🎉</div>
                <h2 style="font-family:'Montserrat',sans-serif;font-size:22px;font-weight:800;color:var(--green-primary);margin-bottom:8px;">Kampanye Dibuka!</h2>
                <p style="color:var(--gray-text);font-size:14px;margin-bottom:24px;">Kampanye berhasil dibuat dan sudah bisa dilihat publik.</p>
                <a href="kelola_kampanye.php" class="btn btn-primary" style="border-radius:var(--radius-sm);">Lihat Kampanye Saya</a>
            </div>
        </div>
    <?php else: ?>

        <div class="pg">

            <?php if ($errors): ?>
                <ul class="err-list">
                    <?php foreach ($errors as $e): ?><li>⚠️ <?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="mainForm">
                <!-- Foto input hidden (ikut form POST) -->
                <input type="file" name="foto" id="fotoHidden" style="display:none" accept="image/*">

                <!-- CARD 1: Nominal + Penggalang -->
                <div class="bd-card">
                    <label class="bd-lbl">Tentukan Nominal Donasi (Target)</label>
                    <input type="number" name="nominal" class="bd-input" id="nominalInput"
                        placeholder="Masukkan target (Rp)" min="1"
                        value="<?= htmlspecialchars($_POST['nominal'] ?? '') ?>">
                    <div class="penggalang-bar">
                        Penggalang Dana : <strong><?= htmlspecialchars($username) ?></strong>
                    </div>
                </div>

                <!-- CARD 2: Detail Kampanye -->
                <div class="bd-card">
                    <div class="bd-grid">
                        <div>
                            <label class="bd-lbl">Judul Donasi</label>
                            <input type="text" name="judul" class="bd-input"
                                placeholder="Judul kampanye..."
                                value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
                        </div>
                        <div>
                            <label class="bd-lbl">Tambah Tag / Kategori</label>
                            <select name="tag" class="bd-select">
                                <option value="">Pilih kategori</option>
                                <?php foreach (['Bencana' => 'Bencana Alam', 'Pendidikan' => 'Pendidikan', 'Kesehatan' => 'Kesehatan', 'FasilitasUmum' => 'Fasilitas Umum'] as $v => $lbl): ?>
                                    <option value="<?= $v ?>" <?= ($_POST['tag'] ?? '') === $v ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="full">
                            <label class="bd-lbl">Tambahkan Cerita / Deskripsi</label>
                            <textarea name="cerita" class="bd-textarea"
                                placeholder="Ceritakan kondisi dan mengapa donasi ini penting..."><?= htmlspecialchars($_POST['cerita'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="bd-lbl">Deadline Donasi</label>
                            <?php
                            $dl = $_POST['deadline'] ?? '';
                            $ddv = $dl ? date('d', strtotime($dl)) : '';
                            $mmv = $dl ? date('m', strtotime($dl)) : '';
                            $yyyyv = $dl ? date('Y', strtotime($dl)) : '';
                            ?>
                            <div class="dl-row">
                                <input type="text" name="dd" placeholder="DD" maxlength="2" value="<?= $ddv ?>">
                                <span class="dl-sep">/</span>
                                <input type="text" name="mm" placeholder="MM" maxlength="2" value="<?= $mmv ?>">
                                <span class="dl-sep">/</span>
                                <input type="text" name="yyyy" placeholder="YYYY" maxlength="4" value="<?= $yyyyv ?>">
                            </div>
                            <input type="hidden" name="deadline" id="deadlineHidden" value="<?= htmlspecialchars($dl) ?>">
                        </div>
                        <div>
                            <label class="bd-lbl">Lokasi</label>
                            <input type="text" name="lokasi" class="bd-input"
                                placeholder="cth. Aceh, Sumatra Utara"
                                value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- METODE PENCAIRAN -->
                    <label class="bd-lbl" style="margin-top:4px;">Metode Pencairan Dana</label>
                    <div class="metode-grid">
                        <?php
                        $sel_metode = $_POST['metode'] ?? [];
                        foreach (['QRIS', 'Rekening', 'E-Wallet', 'BTC'] as $m):
                            $chk = in_array($m, $sel_metode) ? 'checked' : '';
                        ?>
                            <label class="metode-cb <?= $chk ? 'checked' : '' ?>" data-metode="<?= $m ?>">
                                <input type="checkbox" name="metode[]" value="<?= $m ?>" <?= $chk ?>>
                                <span><?= $m ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Detail QRIS -->
                    <div class="metode-detail <?= in_array('QRIS', $sel_metode) ? 'show' : '' ?>" id="detail-QRIS">
                        <label class="md-lbl">📷 Upload Gambar QRIS</label>
                        <label class="file-drop-sm" for="qrisFile">
                            <div style="font-size:22px;margin-bottom:4px;">🖼️</div>
                            <div id="qrisFileName">Klik untuk unggah gambar QRIS</div>
                            <div style="font-size:10px;color:#aaa;margin-top:3px;">JPG, PNG — maks 5MB</div>
                            <input type="file" name="qris_foto" id="qrisFile" accept="image/*"
                                onchange="previewQRIS(this)">
                        </label>
                        <img id="qrisPreview" src="" alt="QRIS Preview">
                    </div>

                    <!-- Detail Rekening -->
                    <div class="metode-detail <?= in_array('Rekening', $sel_metode) ? 'show' : '' ?>" id="detail-Rekening">
                        <label class="md-lbl">🏦 Nomor Rekening Bank</label>
                        <input type="text" name="no_rekening" class="bd-input"
                            placeholder="cth. 1234-5678-9012 (BRI a.n. Nama)"
                            value="<?= htmlspecialchars($_POST['no_rekening'] ?? '') ?>">
                    </div>

                    <!-- Detail E-Wallet -->
                    <div class="metode-detail <?= in_array('E-Wallet', $sel_metode) ? 'show' : '' ?>" id="detail-E-Wallet">
                        <label class="md-lbl">📱 Nomor E-Wallet</label>
                        <input type="text" name="no_ewallet" class="bd-input"
                            placeholder="cth. 0811-1111-1111 (DANA / OVO / GoPay)"
                            value="<?= htmlspecialchars($_POST['no_ewallet'] ?? '') ?>">
                    </div>

                    <!-- Detail BTC -->
                    <div class="metode-detail <?= in_array('BTC', $sel_metode) ? 'show' : '' ?>" id="detail-BTC">
                        <label class="md-lbl">₿ Alamat Bitcoin (BTC)</label>
                        <input type="text" name="no_btc" class="bd-input"
                            placeholder="cth. bc1qxy2kgdygjrsqtzq2n0yrf2..."
                            value="<?= htmlspecialchars($_POST['no_btc'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn-submit" onclick="buildDeadline()">
                        Buka Kampanye Donasi
                    </button>
                </div>

            </form>
        </div>

    <?php endif; ?>

    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
    <script>
        // Initialize metode listeners
        document.querySelectorAll('.metode-cb input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const label = this.parentElement;
                const metode = label.dataset.metode;
                const detail = document.getElementById('detail-' + metode);

                if (this.checked) {
                    label.classList.add('checked');
                    if (detail) detail.classList.add('show');
                } else {
                    label.classList.remove('checked');
                    if (detail) detail.classList.remove('show');
                }
            });
        });

        // Preview foto kampanye
        function previewFoto(input) {
            if (!input.files || !input.files[0]) return;
            const r = new FileReader();
            r.onload = function(e) {
                const img = document.getElementById('previewImg');
                img.src = e.target.result;
                img.classList.add('vis');
                document.getElementById('fotoPlaceholder').style.display = 'none';
                // copy ke hidden input
                const dt = new DataTransfer();
                dt.items.add(input.files[0]);
                document.getElementById('fotoHidden').files = dt.files;
            };
            r.readAsDataURL(input.files[0]);
        }

        // Preview QRIS
        function previewQRIS(input) {
            if (!input.files || !input.files[0]) return;
            const r = new FileReader();
            r.onload = function(e) {
                const img = document.getElementById('qrisPreview');
                img.src = e.target.result;
                img.classList.add('vis');
                document.getElementById('qrisFileName').textContent = '✓ ' + input.files[0].name;
            };
            r.readAsDataURL(input.files[0]);
        }

        // Auto-jump deadline DD→MM→YYYY
        ['dd', 'mm'].forEach(function(id) {
            const elem = document.querySelector('input[name="' + id + '"]');
            if (elem) {
                elem.addEventListener('input', function() {
                    if (this.value.length === 2) {
                        const next = id === 'dd' ? 'mm' : 'yyyy';
                        const nextElem = document.querySelector('input[name="' + next + '"]');
                        if (nextElem) nextElem.focus();
                    }
                });
            }
        });

        // Build hidden deadline value before submit
        function buildDeadline() {
            const dd = document.querySelector('input[name="dd"]').value.padStart(2, '0');
            const mm = document.querySelector('input[name="mm"]').value.padStart(2, '0');
            const yyyy = document.querySelector('input[name="yyyy"]').value;
            if (dd && mm && yyyy.length === 4) {
                document.getElementById('deadlineHidden').value = yyyy + '-' + mm + '-' + dd;
            }
        }
    </script>
</body>

</html>