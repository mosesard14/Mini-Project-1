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

// ── HANDLE POST: Verifikasi donasi ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'], $_POST['did'])) {
    $don_id = intval($_POST['did']);
    $action = $_POST['verify'] === 'accept' ? 'verified' : 'rejected';
    // [FIX 4+5] Hanya ambil donasi yang masih pending — cegah dobel proses
    $don = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT d.nominal FROM donasi d
        JOIN kampanye k ON k.id=d.kampanye_id
        WHERE d.id=$don_id AND k.pengelola_id=$user_id AND d.status='pending' LIMIT 1"
    ));
    if ($don) {
        mysqli_query(
            $koneksi,
            "UPDATE donasi SET status='$action' WHERE id=$don_id AND status='pending'"
        );
        if ($action === 'verified') {
            $nom = (int)$don['nominal'];
            mysqli_query(
                $koneksi,
                "UPDATE kampanye SET terkumpul = terkumpul + $nom WHERE id=$kamp_id"
            );
        }
        $kamp = mysqli_fetch_assoc(mysqli_query(
            $koneksi,
            "SELECT * FROM kampanye WHERE id=$kamp_id LIMIT 1"
        ));
    }
    header("Location: detail_kelola.php?id=$kamp_id&msg=verified");
    exit();
}

// ── HANDLE POST: Hapus kampanye ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
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
    <link rel="stylesheet" href="../style/detail_kelola.css">
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
                            <div class="sum-lbl">Verified</div>
                            <div class="sum-val"><?= rupiah($verified_tot) ?></div>
                        </div>
                        <div class="sum-box yellow">
                            <div class="sum-lbl">Pending</div>
                            <div class="sum-val"><?= rupiah($pending_tot) ?></div>
                        </div>
                        <div class="sum-box red">
                            <div class="sum-lbl">Ditolak</div>
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
                                            <?= $d['status'] === 'verified' ? 'Verified' : ($d['status'] === 'pending' ? 'Pending' : 'Ditolak') ?>
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
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Terima donasi ini?')">
                                                <input type="hidden" name="verify" value="accept">
                                                <input type="hidden" name="did" value="<?= $d['id'] ?>">
                                                <button type="submit" class="btn-acc">Terima</button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Tolak donasi ini?')">
                                                <input type="hidden" name="verify" value="reject">
                                                <input type="hidden" name="did" value="<?= $d['id'] ?>">
                                                <button type="submit" class="btn-rej">Tolak</button>
                                            </form>
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
                    <h4>Hapus Kampanye</h4>
                    <p>Kampanye yang sudah memiliki dana terkumpul ≥ Rp 10.000 tidak dapat dihapus.</p>
                    <form method="POST" onsubmit="return confirm('Yakin hapus kampanye ini? Tindakan tidak dapat dibatalkan.')">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger" style="width:100%;display:block;text-align:center;border-radius:var(--radius-sm);">
                            Hapus Kampanye
                        </button>
                    </form>
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