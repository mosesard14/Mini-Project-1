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
    <style>
        body { background:var(--gray-bg); }
        .pg { max-width:1000px; margin:0 auto; padding:28px 20px 60px; }

        .sec-title { font-family:'Montserrat',sans-serif; font-size:17px; font-weight:800; color:var(--green-primary); margin-bottom:16px; }

        /* Ringkasan */
        .ring-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:28px; }
        .ring-card { background:white; border-radius:var(--radius-md); padding:16px 18px; box-shadow:var(--shadow-sm); animation:fadeInUp .4s ease; }
        .ring-card .rl { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
        .ring-card.green .rl { color:var(--green-primary); }
        .ring-card.yellow .rl { color:#b45309; }
        .ring-card.red .rl { color:#dc2626; }
        .ring-card .rv { font-size:18px; font-weight:800; color:#1a2319; }
        .ring-card .rc { font-size:12px; color:var(--gray-muted); margin-top:2px; }
        .ring-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:4px; }
        .ring-card.green .ring-dot { background:var(--green-primary); }
        .ring-card.yellow .ring-dot { background:#d97706; }
        .ring-card.red .ring-dot { background:#dc2626; }

        /* List item */
        .don-item { display:flex; align-items:center; gap:14px; background:white; border-radius:var(--radius-md); padding:14px 16px; margin-bottom:12px; box-shadow:var(--shadow-sm); transition:var(--transition); animation:fadeInUp .45s ease; }
        .don-item:hover { box-shadow:var(--shadow-md); transform:translateY(-1px); }
        .don-thumb { width:60px; height:50px; border-radius:8px; object-fit:cover; flex-shrink:0; }
        .don-info { flex:1; min-width:0; }
        .don-info h4 { font-weight:700; font-size:13px; color:#1a2319; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .don-info .dk { font-size:11px; color:var(--gray-muted); }
        .don-info .dm { font-size:11px; color:var(--green-primary); font-weight:600; margin-top:2px; }
        .don-right { text-align:right; flex-shrink:0; }
        .don-nom { font-weight:800; font-size:14px; color:var(--green-primary); }
        .don-date { font-size:10px; color:var(--gray-muted); margin-bottom:5px; }
        .sdot { display:inline-flex; align-items:center; gap:3px; font-size:11px; font-weight:700; padding:2px 9px; border-radius:20px; }
        .sdot.verified { background:#dcfce7; color:#15803d; }
        .sdot.pending  { background:#fef9c3; color:#b45309; }
        .sdot.rejected { background:#fee2e2; color:#dc2626; }

        .empty-state { text-align:center; padding:60px 20px; color:var(--gray-muted); }
        .empty-state .ei { font-size:52px; margin-bottom:14px; }

        @media(max-width:560px){ .ring-grid{grid-template-columns:1fr;} }
    </style>
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
