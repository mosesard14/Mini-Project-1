<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pengelola') {
    header('Location: login.php'); exit();
}
$user_id  = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];
$backUrl  = null; $pageTitle = '';

// Handle delete
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $del_id = intval($_GET['id']);
    // Cek apakah terkumpul >= 10000
    $chk = mysqli_fetch_assoc(mysqli_query($koneksi,
        "SELECT terkumpul FROM kampanye WHERE id=$del_id AND pengelola_id=$user_id LIMIT 1"));
    if ($chk && (int)$chk['terkumpul'] >= 10000) {
        header('Location: kelola_kampanye.php?msg=cannot_delete'); exit();
    }
    mysqli_query($koneksi, "DELETE FROM kampanye WHERE id=$del_id AND pengelola_id=$user_id");
    header('Location: kelola_kampanye.php?msg=deleted'); exit();
}

$flash = [
    'deleted'        => ['type'=>'green', 'text'=>'✓ Kampanye berhasil dihapus.'],
    'created'        => ['type'=>'green', 'text'=>'✓ Kampanye baru berhasil dibuat!'],
    'updated'        => ['type'=>'green', 'text'=>'✓ Kampanye berhasil diperbarui.'],
    'cannot_delete'  => ['type'=>'red',   'text'=>'⚠️ Kampanye tidak dapat dihapus karena sudah ada dana masuk (≥ Rp 10.000).'],
];
$msg = $_GET['msg'] ?? '';

// Ambil kampanye milik pengelola dari DB
$res = mysqli_query($koneksi,
    "SELECT id,judul,foto_path,kategori,deadline,terkumpul,target_dana,status
     FROM kampanye WHERE pengelola_id=$user_id ORDER BY created_at DESC");
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
    <style>
        body { background:white; }
        .pg { max-width:1000px; margin:0 auto; padding:20px 20px 60px; }

        /* Flash */
        .flash { border-radius:var(--radius-sm); padding:11px 16px; font-size:13px; font-weight:600; margin-bottom:18px; animation:fadeInUp .3s ease; }
        .flash.green { background:var(--green-light); border-left:4px solid var(--green-primary); color:var(--green-dark); }
        .flash.red   { background:#fee2e2; border-left:4px solid #dc2626; color:#dc2626; }

        /* Banner */
        .banner-kelola { position:relative; border-radius:var(--radius-xl); overflow:hidden; margin-bottom:28px; box-shadow:var(--shadow-md); animation:fadeInUp .4s ease; }
        .banner-kelola img { width:100%; height:200px; object-fit:cover; filter:brightness(.68); display:block; }
        .banner-kelola::after { content:''; position:absolute; inset:0; background:linear-gradient(to bottom,rgba(0,0,0,.10) 30%,rgba(0,0,0,.60) 100%); pointer-events:none; }
        .banner-body { position:absolute; bottom:0; left:0; right:0; padding:24px 28px; display:flex; align-items:flex-end; justify-content:space-between; z-index:1; }
        .banner-body h2 { font-family:'Montserrat',sans-serif; font-size:26px; font-weight:900; font-style:italic; color:white; text-shadow:0 2px 8px rgba(0,0,0,.4); line-height:1.1; }
        .btn-buka { background:rgba(255,255,255,.20); backdrop-filter:blur(6px); border:1.5px solid rgba(255,255,255,.55); color:white; padding:10px 20px; border-radius:30px; font-size:14px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:var(--transition); flex-shrink:0; }
        .btn-buka:hover { background:rgba(255,255,255,.32); }

        /* Section title */
        .sec-title { font-family:'Montserrat',sans-serif; font-size:16px; font-weight:800; color:var(--green-primary); margin-bottom:16px; font-style:italic; }

        /* Campaign card row */
        .camp-row { display:flex; align-items:center; gap:14px; background:#e2e8e4; border-radius:var(--radius-md); padding:13px 16px; margin-bottom:12px; transition:var(--transition); animation:fadeInUp .45s ease; }
        .camp-row:hover { box-shadow:var(--shadow-sm); transform:translateY(-1px); }
        .camp-thumb { width:92px; height:68px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0; }
        .camp-info { flex:1; min-width:0; }
        .camp-info h4 { font-weight:800; font-size:13px; color:var(--green-primary); font-style:italic; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .camp-info .dl { font-size:10px; color:var(--red-accent); font-weight:700; font-style:italic; }
        .camp-info .dl span { color:var(--gray-muted); font-style:normal; font-weight:500; }
        .camp-info .tg { font-size:11px; color:var(--green-primary); font-weight:800; font-style:italic; margin-top:2px; }
        .camp-prog { margin-top:5px; }
        .cpb { height:4px; background:rgba(0,0,0,.12); border-radius:10px; overflow:hidden; }
        .cpb-fill { height:100%; background:linear-gradient(90deg,var(--green-primary),#52c77a); border-radius:10px; }
        .cp-lbl { font-size:9px; font-weight:700; color:var(--gray-muted); margin-top:2px; }
        .btn-kelola { background:var(--green-primary); color:white; border:none; padding:9px 18px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; flex-shrink:0; text-decoration:none; transition:var(--transition); font-family:'Poppins',sans-serif; }
        .btn-kelola:hover { background:var(--green-dark); }

        /* Empty state */
        .empty-state { text-align:center; padding:50px 20px; color:var(--gray-muted); }
        .empty-state .ei { font-size:52px; margin-bottom:14px; }
        .empty-state p { font-size:14px; margin-bottom:18px; }
    </style>
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
                    <div class="cpb"><div class="cpb-fill" style="width:<?= $p ?>%"></div></div>
                    <div class="cp-lbl"><?= rupiah((int)$c['terkumpul']) ?> / <?= rupiah((int)$c['target_dana']) ?></div>
                </div>
            </div>
            <a href="detail_kelola.php?id=<?= $c['id'] ?>" class="btn-kelola">Kelola</a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<footer class="footer"><p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p></footer>
</body>
</html>
