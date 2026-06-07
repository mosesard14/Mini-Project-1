<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pengelola') {
    header('Location: login.php');
    exit();
}
$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$backUrl  = null;
$pageTitle = '';

// Handle delete — [FIX 5] baca dari POST, bukan GET
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isset($_POST['id'])) {
    $del_id = intval($_POST['id']);
    // Cek apakah terkumpul >= 10000
    $chk = mysqli_fetch_assoc(mysqli_query(
        $koneksi,
        "SELECT terkumpul FROM kampanye WHERE id=$del_id AND pengelola_id=$user_id LIMIT 1"
    ));
    if ($chk && (int)$chk['terkumpul'] >= 10000) {
        header('Location: kelola_kampanye.php?msg=cannot_delete');
        exit();
    }
    mysqli_query($koneksi, "DELETE FROM kampanye WHERE id=$del_id AND pengelola_id=$user_id");
    header('Location: kelola_kampanye.php?msg=deleted');
    exit();
}

$flash = [
    'deleted'        => ['type' => 'green', 'text' => '✓ Kampanye berhasil dihapus.'],
    'created'        => ['type' => 'green', 'text' => '✓ Kampanye baru berhasil dibuat!'],
    'updated'        => ['type' => 'green', 'text' => '✓ Kampanye berhasil diperbarui.'],
    'cannot_delete'  => ['type' => 'red',   'text' => '⚠️ Kampanye tidak dapat dihapus karena sudah ada dana masuk (≥ Rp 10.000).'],
];
$msg = $_GET['msg'] ?? '';

// Ambil kampanye milik pengelola dari DB
$res = mysqli_query($koneksi, "SELECT id,judul,foto_path,kategori,deadline,terkumpul,target_dana, status FROM kampanye WHERE pengelola_id=$user_id ORDER BY created_at DESC");
$my_campaigns = [];
while ($r = mysqli_fetch_assoc($res)) $my_campaigns[] = $r;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kampanye Saya — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/kelola_kampanye.css">
</head>

<body>
    <?php include '_navbar.php'; ?>
    <div class="pg">

        <?php if ($msg && isset($flash[$msg])): ?>
            <div class="flash <?= $flash[$msg]['type'] ?>"><?= $flash[$msg]['text'] ?></div>
        <?php endif; ?>

        <!-- BANNER -->
        <div class="banner-kelola">
            <img src="../assets/beranda.jpg" alt="banner">
            <div class="banner-body">
                <h2>Buka Donasi,<br>Beri Donasi</h2>
                <a href="buka_donasi.php" class="btn-buka">+ Buka Donasi</a>
            </div>
        </div>

        <h3 class="sec-title">Kampanye Saya</h3>

        <?php if (empty($my_campaigns)): ?>
            <div class="empty-state">
                <div class="ei">📭</div>
                <p>Anda belum memiliki kampanye. Mulai sekarang!</p>
                <a href="buka_donasi.php" class="btn-kelola" style="display:inline-block;border-radius:20px;padding:10px 24px;">+ Buka Kampanye</a>
            </div>
        <?php else: ?>
            <?php foreach ($my_campaigns as $c):
                $p = pct((int)$c['terkumpul'], (int)$c['target_dana']); ?>
                <div class="camp-row">
                    <img src="../<?= htmlspecialchars($c['foto_path']) ?>" class="camp-thumb"
                        alt="thumb" onerror="this.src='../assets/contoh1.jpg'">
                    <div class="camp-info">
                        <h4><?= htmlspecialchars($c['judul']) ?></h4>
                        <div class="dl">Dibuka Hingga <span><?= date('d M Y', strtotime($c['deadline'])) ?></span></div>
                        <div class="tg">#<?= htmlspecialchars($c['kategori']) ?></div>
                        <div class="camp-prog">
                            <div class="cpb">
                                <div class="cpb-fill" style="width:<?= $p ?>%"></div>
                            </div>
                            <div class="cp-lbl"><?= rupiah((int)$c['terkumpul']) ?> / <?= rupiah((int)$c['target_dana']) ?></div>
                        </div>
                    </div>
                    <a href="detail_kelola.php?id=<?= $c['id'] ?>" class="btn-kelola">Kelola</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
</body>

</html>