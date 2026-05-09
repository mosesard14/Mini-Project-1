<?php
session_start();
include '../koneksi.php';

$user_id  = $_SESSION['user_id']  ?? null;
$username = $_SESSION['username'] ?? null;
$role     = $_SESSION['role']     ?? 'donatur';
$backUrl  = null; $pageTitle = '';

// ── SEARCH + FILTER ──────────────────────────────────────────
$search   = trim($_GET['q']   ?? '');
$kategori = trim($_GET['kat'] ?? '');
$page     = max(1, intval($_GET['pg'] ?? 1));
$per_page = 6;
$offset   = ($page - 1) * $per_page;

$where = "WHERE k.deadline >= CURDATE() AND k.status = 'aktif'";
if ($search)   $where .= " AND (k.judul LIKE '%" . esc($search) . "%' OR k.kategori LIKE '%" . esc($search) . "%' OR k.lokasi LIKE '%" . esc($search) . "%')";
if ($kategori) $where .= " AND k.kategori = '" . esc($kategori) . "'";

$total_row = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kampanye k $where"));
$total     = (int)($total_row[0] ?? 0);
$pages     = max(1, ceil($total / $per_page));

$result = mysqli_query($koneksi,
    "SELECT k.*, u.nama_org, u.username AS pnm
     FROM kampanye k
     JOIN users u ON u.id = k.pengelola_id
     $where
     ORDER BY k.deadline ASC, k.terkumpul ASC
     LIMIT $per_page OFFSET $offset");
$campaigns = [];
while ($r = mysqli_fetch_assoc($result)) $campaigns[] = $r;

// Kampanye milik pengelola (sidebar)
$my_camps = [];
if ($role === 'pengelola' && $user_id) {
    $res2 = mysqli_query($koneksi,
        "SELECT id,judul,foto_path,kategori,deadline,terkumpul,target_dana
         FROM kampanye WHERE pengelola_id=$user_id AND status='aktif' ORDER BY created_at DESC");
    while ($r = mysqli_fetch_assoc($res2)) $my_camps[] = $r;
}

$kat_list = ['Bencana'=>'🌊 Bencana Alam','Pendidikan'=>'📚 Pendidikan','Kesehatan'=>'🏥 Kesehatan','FasilitasUmum'=>'🏗️ Fasilitas Umum'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <style>
        body { background:var(--gray-bg); }
        .pg { max-width:1000px; margin:0 auto; padding:28px 20px 60px; }

        .banner {
            background:linear-gradient(rgba(0,0,0,.30),rgba(0,0,0,.40)),url('../assets/beranda.jpg') center/cover no-repeat;
            border-radius:var(--radius-xl); height:260px;
            display:flex; align-items:flex-end; padding:32px 36px;
            color:white; position:relative; overflow:hidden;
            box-shadow:var(--shadow-md); margin-bottom:32px;
            animation:fadeInUp .5s ease;
        }
        .banner::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(30,107,62,.55) 0%,rgba(15,61,34,.25) 100%); border-radius:inherit; }
        .banner-content { position:relative; z-index:1; }
        .banner-content h1 { font-family:'Montserrat',sans-serif; font-size:2.4rem; font-weight:900; line-height:1.1; margin-bottom:16px; text-shadow:0 2px 12px rgba(0,0,0,.3); }
        .banner-btn { background:rgba(255,255,255,.18); backdrop-filter:blur(8px); border:1.5px solid rgba(255,255,255,.55); color:white; padding:10px 22px; border-radius:30px; cursor:pointer; font-weight:700; font-family:'Montserrat',sans-serif; font-size:14px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:var(--transition); }
        .banner-btn:hover { background:rgba(255,255,255,.30); transform:translateY(-1px); }

        /* Kampanye saya */
        .section-title { font-family:'Montserrat',sans-serif; font-size:17px; font-weight:800; color:var(--green-primary); margin-bottom:14px; }
        .my-camps { margin-bottom:36px; }
        .camp-row { display:flex; align-items:center; gap:14px; background:white; border-radius:var(--radius-md); padding:12px 16px; margin-bottom:10px; box-shadow:var(--shadow-sm); transition:var(--transition); }
        .camp-row:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
        .camp-thumb { width:68px; height:54px; border-radius:8px; object-fit:cover; flex-shrink:0; }
        .camp-info { flex:1; min-width:0; }
        .camp-info h4 { font-weight:700; font-size:13px; color:#1a2319; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .camp-info .dl { font-size:10px; color:var(--red-accent); font-weight:700; font-style:italic; }
        .camp-info .tg { font-size:11px; color:var(--green-primary); font-weight:700; }

        /* Search */
        .search-bar { display:flex; margin-bottom:20px; }
        .search-bar input { flex:1; padding:11px 16px; border:1.5px solid var(--gray-border); border-right:none; border-radius:var(--radius-sm) 0 0 var(--radius-sm); font-family:'Poppins',sans-serif; font-size:14px; background:white; outline:none; transition:var(--transition); color:#333; }
        .search-bar input:focus { border-color:var(--green-primary); }
        .search-bar button { background:var(--green-primary); color:white; border:none; padding:0 22px; border-radius:0 var(--radius-sm) var(--radius-sm) 0; font-weight:700; font-size:14px; cursor:pointer; transition:var(--transition); }
        .search-bar button:hover { background:var(--green-dark); }

        /* Kategori tabs */
        .kat-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:22px; }
        .kat-tab { padding:7px 16px; background:white; color:var(--gray-text); border:1.5px solid var(--gray-border); border-radius:30px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; transition:var(--transition); }
        .kat-tab:hover { border-color:var(--green-primary); color:var(--green-primary); }
        .kat-tab.active { background:var(--green-primary); color:white; border-color:var(--green-primary); }

        /* Card grid */
        .card-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; margin-bottom:36px; }
        .card-camp { display:flex; flex-direction:column; background:white; border-radius:var(--radius-md); overflow:hidden; box-shadow:var(--shadow-sm); transition:var(--transition); animation:fadeInUp .4s ease; }
        .card-camp:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }
        .card-img { height:160px; background-size:cover; background-position:center; position:relative; display:flex; flex-direction:column; justify-content:space-between; padding:12px 14px; }
        .card-img::after { content:''; position:absolute; inset:0; background:linear-gradient(to bottom,rgba(0,0,0,.10) 0%,rgba(0,0,0,.55) 100%); }
        .c-tag { position:relative; z-index:1; display:inline-block; background:rgba(255,255,255,.22); backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,.4); color:white; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; align-self:flex-start; }
        .c-bot { position:relative; z-index:1; display:flex; align-items:center; justify-content:space-between; }
        .c-lokasi { color:rgba(255,255,255,.88); font-size:11px; font-weight:600; }
        .c-btn { background:rgba(255,255,255,.22); backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,.55); color:white; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; text-decoration:none; transition:var(--transition); }
        .c-btn:hover { background:rgba(255,255,255,.36); }
        .card-body { padding:14px 16px 16px; display:flex; flex-direction:column; flex:1; }
        .card-ttl { font-size:13px; font-weight:700; color:#1a2319; line-height:1.5; margin-bottom:10px; flex:1; }
        .card-stats { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:6px; }
        .sv-main { font-size:15px; font-weight:800; color:var(--green-primary); }
        .sv-lbl { font-size:10px; font-weight:700; color:var(--gray-muted); font-style:italic; }
        .sv-sub { font-size:12px; font-weight:600; color:var(--gray-muted); font-style:italic; }
        .prog-mini { height:5px; background:var(--gray-border); border-radius:10px; margin:8px 0; overflow:hidden; }
        .prog-mini-fill { height:100%; background:linear-gradient(90deg,var(--green-primary),#52c77a); border-radius:10px; }
        .card-dl { font-size:11px; color:var(--red-accent); font-weight:700; text-align:right; }

        /* Pagination */
        .pagination { display:flex; justify-content:center; gap:6px; margin-top:8px; }
        .pg-btn { padding:7px 14px; border:1.5px solid var(--gray-border); border-radius:var(--radius-sm); font-size:13px; font-weight:600; color:var(--gray-text); text-decoration:none; transition:var(--transition); background:white; }
        .pg-btn:hover { border-color:var(--green-primary); color:var(--green-primary); }
        .pg-btn.active { background:var(--green-primary); color:white; border-color:var(--green-primary); }

        /* Empty */
        .empty-state { text-align:center; padding:60px 20px; color:var(--gray-muted); }
        .empty-state .ei { font-size:52px; margin-bottom:12px; }
    </style>
</head>
<body>
<?php include '_navbar.php'; ?>
<div class="pg">

    <!-- BANNER -->
    <header class="banner">
        <div class="banner-content">
            <h1>Buka Donasi,<br>Beri Donasi</h1>
            <?php if ($role === 'pengelola'): ?>
            <a href="buka_donasi.php" class="banner-btn">+ Buka Donasi</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- KAMPANYE SAYA (pengelola only) -->
    <?php if ($role === 'pengelola' && $my_camps): ?>
    <section class="my-camps">
        <h2 class="section-title">Kampanye Saya</h2>
        <?php foreach ($my_camps as $mc):
            $p2 = pct((int)$mc['terkumpul'], (int)$mc['target_dana']); ?>
        <div class="camp-row">
            <img src="../<?= htmlspecialchars($mc['foto_path']) ?>" class="camp-thumb"
                 alt="thumb" onerror="this.src='../assets/contoh1.jpg'">
            <div class="camp-info">
                <h4><?= htmlspecialchars($mc['judul']) ?></h4>
                <div class="dl">Hingga <?= date('d M Y', strtotime($mc['deadline'])) ?></div>
                <div class="tg">#<?= htmlspecialchars($mc['kategori']) ?></div>
            </div>
            <a href="detail_kelola.php?id=<?= $mc['id'] ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <!-- SEARCH BAR -->
    <form method="GET" action="main.php">
        <?php if ($kategori): ?><input type="hidden" name="kat" value="<?= htmlspecialchars($kategori) ?>"><?php endif; ?>
        <div class="search-bar">
            <input type="text" name="q" placeholder="Cari judul, kategori, atau lokasi..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Cari 🔍</button>
        </div>
    </form>

    <!-- KATEGORI TABS -->
    <div class="kat-tabs">
        <a href="main.php?q=<?= urlencode($search) ?>" class="kat-tab <?= !$kategori?'active':'' ?>">Semua</a>
        <?php foreach ($kat_list as $k => $lbl): ?>
        <a href="main.php?kat=<?= $k ?>&q=<?= urlencode($search) ?>"
           class="kat-tab <?= $kategori===$k?'active':'' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>

    <h3 class="section-title">Temukan Kampanye <span style="font-size:13px;color:var(--gray-muted);font-weight:500;">(<?= $total ?> kampanye aktif)</span></h3>

    <!-- CARDS -->
    <?php if (empty($campaigns)): ?>
    <div class="empty-state">
        <div class="ei">🔍</div>
        <p>Tidak ada kampanye yang cocok dengan pencarian.</p>
    </div>
    <?php else: ?>
    <div class="card-grid">
        <?php foreach ($campaigns as $c):
            $p = pct((int)$c['terkumpul'], (int)$c['target_dana']); ?>
        <div class="card-camp">
            <div class="card-img" style="background-image:url('../<?= htmlspecialchars($c['foto_path']) ?>')">
                <div class="c-tag">#<?= htmlspecialchars($c['kategori']) ?></div>
                <div class="c-bot">
                    <span class="c-lokasi"><?= htmlspecialchars($c['lokasi']) ?></span>
                    <a href="detailDonasi.php?id=<?= $c['id'] ?>" class="c-btn">Detail</a>
                </div>
            </div>
            <div class="card-body">
                <p class="card-ttl"><?= htmlspecialchars($c['judul']) ?></p>
                <div class="card-stats">
                    <div>
                        <div class="sv-lbl">Terkumpul</div>
                        <div class="sv-main"><?= rupiah((int)$c['terkumpul']) ?></div>
                    </div>
                    <div style="text-align:right">
                        <div class="sv-lbl">Dari</div>
                        <div class="sv-sub"><?= rupiah((int)$c['target_dana']) ?></div>
                    </div>
                </div>
                <div class="prog-mini"><div class="prog-mini-fill" style="width:<?= $p ?>%"></div></div>
                <div class="card-dl">⏰ <?= date('d/m/Y', strtotime($c['deadline'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- PAGINATION -->
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="main.php?pg=<?= $i ?>&q=<?= urlencode($search) ?>&kat=<?= urlencode($kategori) ?>"
           class="pg-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</div>
<footer class="footer"><p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p></footer>
</body>
</html>
