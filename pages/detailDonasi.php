<?php
session_start();
include '../koneksi.php';


if (!isset($_SESSION['user_id'])) {
    $id_param = intval($_GET['id'] ?? 0);
    header('Location: login.php?redirect=detailDonasi.php&id=' . $id_param);
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: main.php');
    exit();
}

$row = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT k.*, u.nama_org, u.email AS p_email, u.telepon AS p_telp, u.username AS pnm
    FROM kampanye k JOIN users u ON u.id=k.pengelola_id
    WHERE k.id=$id LIMIT 1"
));
if (!$row) {
    header('Location: main.php');
    exit();
}

$metode = json_decode($row['metode_json'] ?? '[]', true) ?: [];
$p = pct((int)$row['terkumpul'], (int)$row['target_dana']);

// Donasi terverifikasi
$donasi_res = mysqli_query(
    $koneksi,
    "SELECT d.*, u.nama_lengkap, u.foto_profil
    FROM donasi d JOIN users u ON u.id=d.donatur_id
    WHERE d.kampanye_id=$id AND d.status='verified'
    ORDER BY d.created_at DESC LIMIT 10"
);
$donasi_list = [];
while ($dr = mysqli_fetch_assoc($donasi_res)) $donasi_list[] = $dr;

$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role']    ?? 'donatur';
$backUrl = 'main.php';
$pageTitle = 'Detail Kampanye';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($row['judul']) ?> — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/detailDonasi.css">
</head>

<body>
    <?php include '_navbar.php'; ?>
    <div class="pg">

        <!-- HERO -->
        <div class="hero">
            <img src="../<?= htmlspecialchars($row['foto_path']) ?>"
                alt="foto" onerror="this.src='../assets/contoh1.jpg'">
            <div class="hero-body">
                <h1><?= htmlspecialchars($row['judul']) ?></h1>
                <div class="hero-badges">
                    <div class="hbadge">#<?= htmlspecialchars($row['kategori']) ?></div>
                    <div class="hbadge">Lokasi : <?= htmlspecialchars($row['lokasi']) ?></div>
                    <div class="hbadge">Akhir Donasi : <?= date('d M Y', strtotime($row['deadline'])) ?></div>
                </div>
            </div>
            <div class="hero-donate-btn">
                <?php if ($user_id && $role === 'donatur'): ?>
                    <a href="pengajuanDonasi.php?id=<?= $id ?>" class="btn btn-ghost">💚 Donasi Sekarang</a>
                <?php elseif (!$user_id): ?>
                    <a href="login.php" class="btn btn-ghost">Login untuk Donasi</a>
                <?php endif; ?>
            </div>
        </div>


        <div class="org-bar">
            Penyelenggara: <strong><?= htmlspecialchars($row['nama_org'] ?? $row['pnm']) ?></strong>
        </div>

        <div class="two-col">

            <!-- KIRI -->
            <div>
                <!-- progres bar -->
                <div class="sc">
                    <div class="sc-title">Dana Terkumpul</div>
                    <div class="prog-amt"><?= rupiah((int)$row['terkumpul']) ?></div>
                    <div class="prog-target">dari <?= rupiah((int)$row['target_dana']) ?></div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?= $p ?>%"></div>
                    </div>
                    <div class="prog-pct"><?= $p ?>% tercapai</div>
                </div>

                <!-- METODE PEMBAYARAN -->
                <div class="sc">
                    <div class="sc-title">Metode Donasi</div>
                    <div class="pay-list">
                        <?php if (in_array('QRIS', $metode) && $row['qris_path']): ?>
                            <div class="pay-item">
                                <div class="pay-name">QRIS</div>
                                <img src="../<?= htmlspecialchars($row['qris_path']) ?>" alt="QRIS"
                                    onerror="this.style.display='none'">
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('Rekening', $metode) && $row['no_rekening']): ?>
                            <div class="pay-item">
                                <div class="pay-name">Rekening Bank</div>
                                <div class="pay-val"><?= htmlspecialchars($row['no_rekening']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('E-Wallet', $metode) && $row['no_ewallet']): ?>
                            <div class="pay-item">
                                <div class="pay-name">E-Wallet</div>
                                <div class="pay-val"><?= htmlspecialchars($row['no_ewallet']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (in_array('BTC', $metode) && $row['no_btc']): ?>
                            <div class="pay-item">
                                <div class="pay-name">Bitcoin (BTC)</div>
                                <div class="pay-val" style="font-size:12px;"><?= htmlspecialchars($row['no_btc']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DONATUR -->
                <?php if ($donasi_list): ?>
                    <div class="sc">
                        <div class="sc-title">Donatur Terbaru</div>
                        <?php foreach ($donasi_list as $d): ?>
                            <div class="don-item">
                                <img src="../<?= htmlspecialchars($d['foto_profil']) ?>" class="don-av"
                                    alt="av" onerror="this.src='../assets/avatar1.jpeg'">
                                <div>
                                    <div class="don-name"><?= htmlspecialchars($d['nama_lengkap'] ?: 'Anonim') ?></div>
                                    <?php if ($d['pesan']): ?>
                                        <div class="don-pesan">"<?= htmlspecialchars($d['pesan']) ?>"</div>
                                    <?php endif; ?>
                                </div>
                                <div class="don-amt"><?= rupiah((int)$d['nominal']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- KANAN: DETAIL -->
            <div class="sc">
                <img src="../<?= htmlspecialchars($row['foto_path']) ?>" class="cerita-img"
                    alt="foto" onerror="this.src='../assets/contoh1.jpg'">
                <div class="sc-title">Deskripsi Kampanye</div>
                <div class="cerita-text"><?= nl2br(htmlspecialchars($row['cerita'])) ?></div>
            </div>

        </div>
    </div>
    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
    <script>
        setTimeout(function() {
            document.querySelectorAll('.progress-bar-fill').forEach(function(el) {
                el.style.transition = 'width 1.2s ease';
            });
        }, 200);
    </script>
</body>

</html>