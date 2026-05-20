<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pengelola') {
    header('Location: login.php');
    exit();
}
$user_id = (int)$_SESSION['user_id'];
$kamp_id = intval($_GET['id'] ?? 0);
if (!$kamp_id) {
    header('Location: kelola_kampanye.php');
    exit();
}

// Ambil kampanye — pastikan milik pengelola ini
$kamp = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT * FROM kampanye WHERE id=$kamp_id AND pengelola_id=$user_id LIMIT 1"
));
if (!$kamp) {
    header('Location: kelola_kampanye.php');
    exit();
}

$metode_list = json_decode($kamp['metode_json'] ?? '[]', true) ?: [];
$errors = [];
$success_msg = '';

// ── HANDLE POST: Edit kampanye ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $judul   = trim($_POST['judul']   ?? '');
    $cerita  = trim($_POST['cerita']  ?? '');
    $tag     = trim($_POST['tag']     ?? '');
    $lokasi  = trim($_POST['lokasi']  ?? '');
    $target  = intval($_POST['target'] ?? 0);
    $dl      = trim($_POST['deadline'] ?? '');

    if (!$judul)  $errors[] = 'Judul wajib diisi.';
    if (!$tag)    $errors[] = 'Kategori wajib diisi.';
    if ($target < 1) $errors[] = 'Target dana harus lebih dari 0.';
    if (!$dl)     $errors[] = 'Deadline wajib diisi.';

    // Upload foto baru (opsional)
    $foto_path = $kamp['foto_path'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fp = uploadGambar($_FILES['foto'], 'kampanye');
        if ($fp) $foto_path = $fp;
        else $errors[] = 'Format foto tidak valid.';
    }

    if (empty($errors)) {
        $j = esc($judul);
        $c = esc($cerita);
        $tg = esc($tag);
        $l = esc($lokasi);
        $dl_e = esc($dl);
        $f = esc($foto_path);
        mysqli_query(
            $koneksi,
            "UPDATE kampanye SET judul='$j',cerita='$c',kategori='$tg',lokasi='$l',
            target_dana=$target,deadline='$dl_e',foto_path='$f'
            WHERE id=$kamp_id AND pengelola_id=$user_id"
        );
        // Refresh data
        $kamp = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT * FROM kampanye WHERE id=$kamp_id LIMIT 1"
        ));
        $success_msg = 'Kampanye berhasil diperbarui.';
    }
}

// ── HANDLE GET: Verifikasi donasi ──────────────────────────
if (isset($_GET['verify'], $_GET['did'])) {
    $don_id = intval($_GET['did']);
    $action = $_GET['verify'] === 'accept' ? 'verified' : 'rejected';
    // Ambil nominal
    $don = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT d.nominal FROM donasi d
        JOIN kampanye k ON k.id=d.kampanye_id
        WHERE d.id=$don_id AND k.pengelola_id=$user_id LIMIT 1"
    ));
    if ($don) {
        mysqli_query(
            $koneksi,
            "UPDATE donasi SET status='$action' WHERE id=$don_id"
        );
        if ($action === 'verified') {
            $nom = (int)$don['nominal'];
            mysqli_query(
                $koneksi,
                "UPDATE kampanye SET terkumpul = terkumpul + $nom WHERE id=$kamp_id"
            );
        }
        // Refresh kamp
        $kamp = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT * FROM kampanye WHERE id=$kamp_id LIMIT 1"
        ));
    }
    header("Location: detail_kelola.php?id=$kamp_id&msg=verified");
    exit();
}

// ── HANDLE GET: Hapus kampanye ─────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if ((int)$kamp['terkumpul'] >= 10000) {
        header("Location: detail_kelola.php?id=$kamp_id&msg=cannot_delete");
        exit();
    }
    mysqli_query($koneksi, "DELETE FROM kampanye WHERE id=$kamp_id AND pengelola_id=$user_id");
    header('Location: kelola_kampanye.php?msg=deleted');
    exit();
}

// Ambil semua donasi kampanye ini
$don_res = mysqli_query(
    $koneksi,
    "SELECT d.*, u.nama_lengkap, u.email, u.foto_profil
    FROM donasi d JOIN users u ON u.id=d.donatur_id
    WHERE d.kampanye_id=$kamp_id ORDER BY d.created_at DESC"
);
$donasi_list = [];
while ($dr = mysqli_fetch_assoc($don_res)) $donasi_list[] = $dr;

// Hitung ringkasan
$verified_tot = array_sum(array_map(fn($d) => $d['status'] === 'verified' ? (int)$d['nominal'] : 0, $donasi_list));
$pending_tot  = array_sum(array_map(fn($d) => $d['status'] === 'pending' ? (int)$d['nominal'] : 0, $donasi_list));
$rejected_tot = array_sum(array_map(fn($d) => $d['status'] === 'rejected' ? (int)$d['nominal'] : 0, $donasi_list));
$p = pct((int)$kamp['terkumpul'], (int)$kamp['target_dana']);

$flash = [
    'verified'       => ['t' => 'green', 'm' => '✓ Status donasi berhasil diperbarui.'],
    'cannot_delete'  => ['t' => 'red',  'm' => '⚠️ Kampanye tidak dapat dihapus karena sudah ada dana masuk.'],
];
$msg = $_GET['msg'] ?? '';

$backUrl = 'kelola_kampanye.php';
$pageTitle = 'Kelola Kampanye';
$all_metode = ['Bencana', 'Pendidikan', 'Kesehatan', 'FasilitasUmum'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kampanye — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <style>
        body {
            background: var(--gray-bg);
        }

        .pg {
            max-width: 1000px;
            margin: 0 auto;
            padding: 28px 20px 70px;
        }

        /* Flash */
        .flash {
            border-radius: var(--radius-sm);
            padding: 11px 16px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
            animation: fadeInUp .3s ease;
        }

        .flash.green {
            background: var(--green-light);
            border-left: 4px solid var(--green-primary);
            color: var(--green-dark);
        }

        .flash.red {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #dc2626;
        }

        /* Hero */
        .camp-hero {
            position: relative;
            height: 220px;
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: 22px;
            box-shadow: var(--shadow-md);
            animation: fadeInUp .4s ease;
        }

        .camp-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(.68);
        }

        .camp-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0, 0, 0, .05) 30%, rgba(0, 0, 0, .60) 100%);
        }

        .camp-hero-body {
            position: absolute;
            bottom: 20px;
            left: 22px;
            right: 22px;
            z-index: 1;
        }

        .camp-hero-body h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 19px;
            font-weight: 900;
            font-style: italic;
            color: white;
            text-shadow: 0 2px 8px rgba(0, 0, 0, .4);
            margin-bottom: 8px;
        }

        .hbadges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .hb {
            background: rgba(255, 255, 255, .18);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, .4);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* Two col */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            align-items: start;
        }

        @media(max-width:760px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        /* Section card */
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
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1.5px solid var(--gray-border);
            display: flex;
            align-items: center;
            gap: 7px;
        }

        /* Progress */
        .prog-amt {
            font-size: 22px;
            font-weight: 800;
            color: var(--green-primary);
        }

        .prog-target {
            font-size: 13px;
            color: var(--gray-muted);
            font-style: italic;
            margin-bottom: 10px;
        }

        .prog-pct {
            font-size: 11px;
            font-weight: 700;
            color: var(--green-primary);
            text-align: right;
            margin-top: 4px;
            margin-bottom: 16px;
        }

        /* Summary boxes */
        .sum-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }

        .sum-box {
            border-radius: 10px;
            padding: 11px 12px;
        }

        .sum-box.green {
            background: var(--green-light);
        }

        .sum-box.yellow {
            background: #fef9c3;
        }

        .sum-box.red {
            background: #fee2e2;
        }

        .sum-lbl {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .3px;
            margin-bottom: 3px;
        }

        .sum-box.green .sum-lbl {
            color: var(--green-primary);
        }

        .sum-box.yellow .sum-lbl {
            color: #b45309;
        }

        .sum-box.red .sum-lbl {
            color: #dc2626;
        }

        .sum-val {
            font-size: 13px;
            font-weight: 800;
            color: #1a2319;
        }

        /* Donasi list */
        .don-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-border);
        }

        .don-item:last-child {
            border-bottom: none;
        }

        .don-av {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .don-body {
            flex: 1;
            min-width: 0;
        }

        .don-name {
            font-weight: 700;
            font-size: 13px;
            color: #1a2319;
        }

        .don-email {
            font-size: 11px;
            color: var(--gray-muted);
        }

        .don-pesan {
            font-size: 12px;
            color: var(--gray-text);
            font-style: italic;
            margin-top: 2px;
        }

        .don-right {
            text-align: right;
            flex-shrink: 0;
        }

        .don-nom {
            font-weight: 800;
            font-size: 13px;
            color: var(--green-primary);
        }

        .don-date {
            font-size: 10px;
            color: var(--gray-muted);
            margin-bottom: 5px;
        }

        .don-actions {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
            margin-top: 5px;
        }

        .don-actions a,
        .don-actions button {
            padding: 4px 10px;
            border-radius: 6px;
            border: none;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
        }

        .btn-acc {
            background: #dcfce7;
            color: #15803d;
        }

        .btn-acc:hover {
            background: #15803d;
            color: white;
        }

        .btn-rej {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-rej:hover {
            background: #dc2626;
            color: white;
        }

        .btn-bukti-sm {
            background: #e0f0ff;
            color: #2563eb;
        }

        .btn-bukti-sm:hover {
            background: #2563eb;
            color: white;
        }

        /* Status dot */
        .sdot {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .sdot.verified {
            background: #dcfce7;
            color: #15803d;
        }

        .sdot.pending {
            background: #fef9c3;
            color: #b45309;
        }

        .sdot.rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Edit form */
        .fg {
            margin-bottom: 14px;
        }

        .fg label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--green-primary);
            text-transform: uppercase;
            letter-spacing: .3px;
            margin-bottom: 5px;
        }

        .fg input,
        .fg select,
        .fg textarea {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
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

        .fg textarea {
            resize: vertical;
            min-height: 80px;
        }

        .fg-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media(max-width:500px) {
            .fg-row {
                grid-template-columns: 1fr;
            }
        }

        /* Danger zone */
        .danger-zone {
            background: #fff5f5;
            border: 1.5px solid #fecaca;
            border-radius: var(--radius-md);
            padding: 18px 22px;
        }

        .danger-zone h4 {
            color: #dc2626;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .danger-zone p {
            font-size: 12px;
            color: #9a3535;
            margin-bottom: 12px;
        }

        /* Modal bukti */
        .modal-ov {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 400;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-ov.show {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            max-width: 460px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp .3s ease;
        }

        .modal-box h3 {
            font-size: 15px;
            font-weight: 800;
            color: var(--green-primary);
            margin-bottom: 12px;
        }

        .modal-box img {
            width: 100%;
            border-radius: 10px;
            max-height: 340px;
            object-fit: contain;
        }

        /* err list */
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
    </style>
</head>

<body>
    <?php include '_navbar.php'; ?>
    <div class="pg">

        <?php if ($msg && isset($flash[$msg])): ?>
            <div class="flash <?= $flash[$msg]['t'] ?>"><?= $flash[$msg]['m'] ?></div>
        <?php endif; ?>
        <?php if ($success_msg): ?>
            <div class="flash green"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>

        <!-- HERO -->
        <div class="camp-hero">
            <img src="../<?= htmlspecialchars($kamp['foto_path']) ?>"
                alt="foto" onerror="this.src='../assets/contoh1.jpg'">
            <div class="camp-hero-body">
                <h2><?= htmlspecialchars($kamp['judul']) ?></h2>
                <div class="hbadges">
                    <div class="hb">#<?= htmlspecialchars($kamp['kategori']) ?></div>
                    <div class="hb">Lokasi : <?= htmlspecialchars($kamp['lokasi']) ?></div>
                    <div class="hb">Deadline : <?= date('d M Y', strtotime($kamp['deadline'])) ?></div>
                </div>
            </div>
        </div>

        <div class="two-col">

            <!-- KIRI -->
            <div>
                <!-- RINGKASAN DANA -->
                <div class="sc">
                    <div class="sc-title">Ringkasan Dana</div>
                    <div class="prog-amt"><?= rupiah((int)$kamp['terkumpul']) ?></div>
                    <div class="prog-target">dari <?= rupiah((int)$kamp['target_dana']) ?></div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" id="progBar" style="width:0%"></div>
                    </div>
                    <div class="prog-pct"><?= $p ?>% tercapai</div>
                    <div class="sum-grid">
                        <div class="sum-box green">
                            <div class="sum-lbl">✅ Verified</div>
                            <div class="sum-val"><?= rupiah($verified_tot) ?></div>
                        </div>
                        <div class="sum-box yellow">
                            <div class="sum-lbl">⏳ Pending</div>
                            <div class="sum-val"><?= rupiah($pending_tot) ?></div>
                        </div>
                        <div class="sum-box red">
                            <div class="sum-lbl">❌ Ditolak</div>
                            <div class="sum-val"><?= rupiah($rejected_tot) ?></div>
                        </div>
                    </div>
                </div>

                <!-- DAFTAR DONATUR -->
                <div class="sc">
                    <div class="sc-title">Daftar Donatur
                        <span style="font-size:11px;color:var(--gray-muted);font-weight:500;">(<?= count($donasi_list) ?> donasi)</span>
                    </div>
                    <?php if (empty($donasi_list)): ?>
                        <p style="color:var(--gray-muted);font-size:13px;text-align:center;padding:24px 0;">Belum ada donasi masuk.</p>
                    <?php else: ?>
                        <?php foreach ($donasi_list as $d): ?>
                            <div class="don-item">
                                <img src="../<?= htmlspecialchars($d['foto_profil']) ?>" class="don-av"
                                    alt="av" onerror="this.src='../assets/avatar1.jpeg'">
                                <div class="don-body">
                                    <div class="don-name"><?= htmlspecialchars($d['nama_lengkap'] ?: 'Anonim') ?></div>
                                    <div class="don-email"><?= htmlspecialchars($d['email']) ?></div>
                                    <?php if ($d['pesan']): ?>
                                        <div class="don-pesan">"<?= htmlspecialchars($d['pesan']) ?>"</div>
                                    <?php endif; ?>
                                    <div style="margin-top:4px;">
                                        <span class="sdot <?= $d['status'] ?>">
                                            <?= $d['status'] === 'verified' ? '✅ Verified' : ($d['status'] === 'pending' ? '⏳ Pending' : '❌ Ditolak') ?>
                                        </span>
                                        <span style="font-size:10px;color:var(--gray-muted);margin-left:6px;"><?= $d['metode'] ?></span>
                                    </div>
                                </div>
                                <div class="don-right">
                                    <div class="don-date"><?= date('d/m/Y', strtotime($d['created_at'])) ?></div>
                                    <div class="don-nom"><?= rupiah((int)$d['nominal']) ?></div>
                                    <div class="don-actions">
                                        <?php if ($d['bukti_path']): ?>
                                            <button class="btn-bukti-sm"
                                                onclick="showBukti('<?= htmlspecialchars('../' . $d['bukti_path']) ?>')">Bukti</button>
                                        <?php endif; ?>
                                        <?php if ($d['status'] === 'pending'): ?>
                                            <a href="detail_kelola.php?id=<?= $kamp_id ?>&verify=accept&did=<?= $d['id'] ?>"
                                                class="btn-acc" onclick="return confirm('Terima donasi ini?')">✓ Terima</a>
                                            <a href="detail_kelola.php?id=<?= $kamp_id ?>&verify=reject&did=<?= $d['id'] ?>"
                                                class="btn-rej" onclick="return confirm('Tolak donasi ini?')">✗ Tolak</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KANAN -->
            <div>
                <!-- EDIT KAMPANYE -->
                <div class="sc">
                    <div class="sc-title">Edit Kampanye</div>

                    <?php if ($errors): ?>
                        <ul class="err-list">
                            <?php foreach ($errors as $e): ?><li>⚠️ <?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <div class="fg">
                            <label>Judul</label>
                            <input type="text" name="judul" value="<?= htmlspecialchars($kamp['judul']) ?>" required>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Kategori</label>
                                <select name="tag" required>
                                    <?php foreach (['Bencana' => 'Bencana', 'Pendidikan' => 'Pendidikan', 'Kesehatan' => 'Kesehatan', 'FasilitasUmum' => 'Fasilitas Umum'] as $v => $lbl): ?>
                                        <option value="<?= $v ?>" <?= $kamp['kategori'] === $v ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="fg">
                                <label>Target (Rp)</label>
                                <input type="number" name="target" value="<?= $kamp['target_dana'] ?>" min="1" required>
                            </div>
                        </div>
                        <div class="fg-row">
                            <div class="fg">
                                <label>Deadline</label>
                                <input type="date" name="deadline" value="<?= $kamp['deadline'] ?>" required>
                            </div>
                            <div class="fg">
                                <label>Lokasi</label>
                                <input type="text" name="lokasi" value="<?= htmlspecialchars($kamp['lokasi']) ?>">
                            </div>
                        </div>
                        <div class="fg">
                            <label>Cerita / Deskripsi</label>
                            <textarea name="cerita"><?= htmlspecialchars($kamp['cerita']) ?></textarea>
                        </div>
                        <div class="fg">
                            <label>Ganti Foto (opsional)</label>
                            <input type="file" name="foto" accept="image/*"
                                style="background:white;padding:8px;border:1.5px solid var(--gray-border);border-radius:var(--radius-sm);">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;border-radius:var(--radius-sm);">
                            Simpan Perubahan
                        </button>
                    </form>
                </div>

                <!-- DANGER ZONE -->
                <div class="danger-zone">
                    <h4>⚠️ Hapus Kampanye</h4>
                    <p>Kampanye yang sudah memiliki dana terkumpul ≥ Rp 10.000 tidak dapat dihapus.</p>
                    <a href="detail_kelola.php?id=<?= $kamp_id ?>&action=delete"
                        class="btn btn-danger" style="width:100%;display:block;text-align:center;border-radius:var(--radius-sm);"
                        onclick="return confirm('Yakin hapus kampanye ini? Tindakan tidak dapat dibatalkan.')">
                        Hapus Kampanye
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL: Lihat bukti transfer -->
    <div class="modal-ov" id="modalBukti">
        <div class="modal-box">
            <h3>📎 Bukti Transfer</h3>
            <img src="" id="buktiImg" alt="bukti">
            <button class="btn btn-outline btn-sm" style="width:100%;margin-top:14px;"
                onclick="document.getElementById('modalBukti').classList.remove('show')">Tutup</button>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
    <script>
        // Animate progress bar on load
        setTimeout(function() {
            document.getElementById('progBar').style.width = '<?= $p ?>%';
        }, 300);

        // Show bukti modal
        function showBukti(src) {
            document.getElementById('buktiImg').src = src;
            document.getElementById('modalBukti').classList.add('show');
        }
        document.getElementById('modalBukti').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });
    </script>
</body>

</html>