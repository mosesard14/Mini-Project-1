<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (($_SESSION['role'] ?? '') !== 'donatur') { header('Location: main.php'); exit(); }

$user_id   = (int)$_SESSION['user_id'];
$backUrl   = 'main.php';
$pageTitle = 'Riwayat Donasi';

// Ambil riwayat donasi dari DB
$res = mysqli_query($koneksi,
    "SELECT d.*, k.judul, k.foto_path, k.kategori
     FROM donasi d
     JOIN kampanye k ON k.id = d.kampanye_id
     WHERE d.donatur_id = $user_id
     ORDER BY d.created_at DESC");
$riwayat = [];
while ($r = mysqli_fetch_assoc($res)) $riwayat[] = $r;

// Ringkasan
$rq = mysqli_query($koneksi,
    "SELECT status, COUNT(*) AS jumlah, SUM(nominal) AS total
     FROM donasi WHERE donatur_id=$user_id GROUP BY status");
$ring = [];
while ($rr = mysqli_fetch_assoc($rq)) $ring[$rr['status']] = $rr;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Donasi — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/riwayat_donasi.css">
</head>
<body>
<?php include '_navbar.php'; ?>
<div class="pg">

    <h2 class="sec-title">Riwayat Donasi Saya</h2>

    <!-- RINGKASAN -->
    <div class="ring-grid">
        <div class="ring-card green">
            <div class="rl"><span class="ring-dot"></span>Verified</div>
            <div class="rv"><?= rupiah((int)($ring['verified']['total'] ?? 0)) ?></div>
            <div class="rc"><?= (int)($ring['verified']['jumlah'] ?? 0) ?> donasi diterima</div>
        </div>
        <div class="ring-card yellow">
            <div class="rl"><span class="ring-dot"></span>Pending</div>
            <div class="rv"><?= rupiah((int)($ring['pending']['total'] ?? 0)) ?></div>
            <div class="rc"><?= (int)($ring['pending']['jumlah'] ?? 0) ?> menunggu verifikasi</div>
        </div>
        <div class="ring-card red">
            <div class="rl"><span class="ring-dot"></span>Ditolak</div>
            <div class="rv"><?= rupiah((int)($ring['rejected']['total'] ?? 0)) ?></div>
            <div class="rc"><?= (int)($ring['rejected']['jumlah'] ?? 0) ?> donasi ditolak</div>
        </div>
    </div>

    <!-- LIST -->
    <?php if (empty($riwayat)): ?>
    <div class="empty-state">
        <div class="ei">📜</div>
        <p>Anda belum pernah berdonasi.</p>
        <a href="main.php" class="btn btn-primary">Temukan Kampanye</a>
    </div>
    <?php else: ?>
        <?php foreach ($riwayat as $d): ?>
        <div class="don-item">
            <img src="../<?= htmlspecialchars($d['foto_path']) ?>" class="don-thumb"
                 alt="thumb" onerror="this.src='../assets/contoh1.jpg'">
            <div class="don-info">
                <h4><?= htmlspecialchars($d['judul']) ?></h4>
                <div class="dk">#<?= htmlspecialchars($d['kategori']) ?></div>
                <div class="dm">via <?= htmlspecialchars($d['metode']) ?></div>
                <?php if ($d['pesan']): ?>
                <div style="font-size:11px;color:var(--gray-muted);font-style:italic;margin-top:2px;">"<?= htmlspecialchars($d['pesan']) ?>"</div>
                <?php endif; ?>
            </div>
            <div class="don-right">
                <div class="don-date"><?= date('d M Y', strtotime($d['created_at'])) ?></div>
                <div class="don-nom"><?= rupiah((int)$d['nominal']) ?></div>
                <div style="margin-top:5px;">
                    <span class="sdot <?= $d['status'] ?>">
                        <?= $d['status']==='verified'?'✅ Verified':($d['status']==='pending'?'⏳ Pending':'❌ Ditolak') ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<footer class="footer"><p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p></footer>
</body>
</html>
