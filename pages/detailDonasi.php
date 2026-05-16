<?php
session_start();
include '../koneksi.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: main.php'); exit(); }

$row = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT k.*, u.nama_org, u.email AS p_email, u.telepon AS p_telp, u.username AS pnm
     FROM kampanye k JOIN users u ON u.id=k.pengelola_id
     WHERE k.id=$id LIMIT 1"));
if (!$row) { header('Location: main.php'); exit(); }

$metode = json_decode($row['metode_json'] ?? '[]', true) ?: [];
$p = pct((int)$row['terkumpul'], (int)$row['target_dana']);

// Donasi terverifikasi
$donasi_res = mysqli_query($koneksi,
    "SELECT d.*, u.nama_lengkap, u.foto_profil
     FROM donasi d JOIN users u ON u.id=d.donatur_id
     WHERE d.kampanye_id=$id AND d.status='verified'
     ORDER BY d.created_at DESC LIMIT 10");
$donasi_list = [];
while ($dr = mysqli_fetch_assoc($donasi_res)) $donasi_list[] = $dr;

$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role']    ?? 'donatur';
$backUrl = 'main.php'; $pageTitle = 'Detail Kampanye';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($row['judul']) ?> — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <style>
        body { background:var(--gray-bg); }
        .pg { max-width:1000px; margin:0 auto; padding:28px 20px 60px; }

        .hero { position:relative; height:260px; border-radius:var(--radius-xl); overflow:hidden; margin-bottom:24px; box-shadow:var(--shadow-md); animation:fadeInUp .4s ease; }
        .hero img { width:100%; height:100%; object-fit:cover; filter:brightness(.70); }
        .hero::after { content:''; position:absolute; inset:0; background:linear-gradient(to bottom,rgba(0,0,0,.05) 40%,rgba(0,0,0,.55) 100%); }
        .hero-body { position:absolute; bottom:22px; left:24px; right:24px; z-index:1; }
        .hero-body h1 { font-family:'Montserrat',sans-serif; font-size:22px; font-weight:900; font-style:italic; color:white; text-shadow:0 2px 10px rgba(0,0,0,.4); margin-bottom:8px; }
        .hero-badges { display:flex; gap:8px; flex-wrap:wrap; }
        .hbadge { background:rgba(255,255,255,.18); backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,.4); color:white; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
        .hero-donate-btn { position:absolute; top:18px; right:22px; z-index:2; }

        /* two col */
        .two-col { display:grid; grid-template-columns:320px 1fr; gap:22px; align-items:start; }
        @media(max-width:760px){ .two-col{grid-template-columns:1fr;} }

        .sc { background:white; border-radius:var(--radius-md); padding:22px 24px; box-shadow:var(--shadow-sm); margin-bottom:20px; animation:fadeInUp .5s ease; }
        .sc-title { font-family:'Montserrat',sans-serif; font-size:14px; font-weight:800; color:var(--green-primary); margin-bottom:14px; padding-bottom:10px; border-bottom:1.5px solid var(--gray-border); }

        /* progress */
        .prog-amt { font-size:24px; font-weight:800; color:var(--green-primary); margin-bottom:4px; }
        .prog-target { font-size:14px; color:var(--gray-muted); font-style:italic; margin-bottom:12px; }
        .prog-pct { font-size:12px; font-weight:700; color:var(--green-primary); text-align:right; margin-top:4px; }

        /* penyelenggara */
        .org-bar { background:var(--green-light); border-left:4px solid var(--green-primary); border-radius:var(--radius-sm); padding:10px 14px; font-size:13px; color:var(--gray-text); margin-bottom:12px; }
        .org-bar strong { color:var(--green-primary); }

        /* metode pembayaran */
        .pay-list { display:flex; flex-direction:column; gap:10px; }
        .pay-item { background:#f9faf9; border:1.5px solid var(--gray-border); border-radius:var(--radius-sm); padding:12px 14px; }
        .pay-item .pay-name { font-size:11px; font-weight:800; text-transform:uppercase; color:var(--green-primary); margin-bottom:5px; letter-spacing:.4px; }
        .pay-item .pay-val { font-size:14px; font-weight:600; color:#333; word-break:break-all; }
        .pay-item img { width:110px; height:110px; object-fit:cover; border-radius:8px; margin-top:6px; }

        /* donatur */
        .don-item { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--gray-border); }
        .don-item:last-child { border-bottom:none; }
        .don-av { width:34px; height:34px; border-radius:50%; object-fit:cover; }
        .don-name { font-weight:700; font-size:13px; color:#1a2319; }
        .don-pesan { font-size:11px; color:var(--gray-muted); font-style:italic; }
        .don-amt { font-weight:800; font-size:13px; color:var(--green-primary); margin-left:auto; }

        /* cerita */
        .cerita-text { font-size:14px; line-height:1.9; color:var(--gray-text); }
        .cerita-img { width:100%; height:220px; object-fit:cover; border-radius:var(--radius-sm); margin-bottom:16px; }
    </style>
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

    <!-- ORG BAR -->
    <div class="org-bar">
        Penyelenggara: <strong><?= htmlspecialchars($row['nama_org'] ?? $row['pnm']) ?></strong>
    </div>

    <div class="two-col">

        <!-- KIRI -->
        <div>
            <!-- PROGRESS -->
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
<footer class="footer"><p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p></footer>
<script>
setTimeout(function(){
    document.querySelectorAll('.progress-bar-fill').forEach(function(el){
        el.style.transition='width 1.2s ease';
    });
},200);
</script>
</body>
</html>
