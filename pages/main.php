<?php
session_start();
include '../koneksi.php';

// ── PROTEKSI: USER HARUS LOGIN ──────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id  = $_SESSION['user_id']  ?? null;
$username = $_SESSION['username'] ?? null;
$role     = $_SESSION['role']     ?? 'donatur';
$backUrl  = null;
$pageTitle = '';

// ── SEARCH + FILTER ──────────────────────────────────────────
$search   = trim($_GET['q']   ?? '');
$kategori = trim($_GET['kat'] ?? '');
$lokasi   = trim($_GET['lok'] ?? '');
$rentang  = trim($_GET['ran'] ?? '');
$page     = max(1, intval($_GET['pg'] ?? 1));
$per_page = 6;
$offset   = ($page - 1) * $per_page;

// ── HELPER: Fungsi untuk mengkonversi rentang dana
function checkRentangDana($target_dana, $rentang): bool
{
    $target = (int)$target_dana;
    return match ($rentang) {
        'r1' => $target < 1000000,
        'r2' => $target >= 1000000 && $target < 5000000,
        'r3' => $target >= 5000000 && $target < 10000000,
        'r4' => $target >= 10000000 && $target < 50000000,
        'r5' => $target >= 50000000 && $target < 100000000,
        'r6' => $target >= 100000000 && $target < 200000000,
        'r7' => $target >= 200000000,
        default => true
    };
}

$where = "WHERE k.deadline >= CURDATE() AND k.status = 'aktif'";
if ($search)   $where .= " AND (k.judul LIKE '%" . esc($search) . "%' OR k.kategori LIKE '%" . esc($search) . "%' OR k.lokasi LIKE '%" . esc($search) . "%' OR DATE_FORMAT(k.deadline,'%d %m %Y') LIKE '%" . esc($search) . "%' OR DATE_FORMAT(k.deadline,'%Y-%m-%d') LIKE '%" . esc($search) . "%')";
if ($kategori) $where .= " AND k.kategori = '" . esc($kategori) . "'";
if ($lokasi)   $where .= " AND k.lokasi LIKE '%" . esc($lokasi) . "%'";

$total_row = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kampanye k $where"));
$total_before_filter     = (int)($total_row[0] ?? 0);

// Ambil data utk di filter 
$result_all = mysqli_query(
    $koneksi,
    "SELECT k.*, u.nama_org, u.username AS pnm
    FROM kampanye k
    JOIN users u ON u.id = k.pengelola_id
    $where
    ORDER BY k.deadline ASC, k.terkumpul ASC"
);

$all_campaigns = [];
while ($r = mysqli_fetch_assoc($result_all)) {
    // Filter rentang dana 
    if ($rentang && !checkRentangDana($r['target_dana'], $rentang)) {
        continue;
    }
    $all_campaigns[] = $r;
}

$total = count($all_campaigns);
$pages = max(1, ceil($total / $per_page));
$campaigns = array_slice($all_campaigns, $offset, $per_page);

// Ambil daftar lokasi dr db
$lok_result = mysqli_query($koneksi, "SELECT DISTINCT k.lokasi FROM kampanye k WHERE k.status = 'aktif' AND k.deadline >= CURDATE() ORDER BY k.lokasi ASC");
$lokasi_list = [];
while ($r = mysqli_fetch_assoc($lok_result)) {
    if (!empty($r['lokasi'])) {
        $lokasi_list[] = $r['lokasi'];
    }
}

// Kampanye(pengelola)
$my_camps = [];
if ($role === 'pengelola' && $user_id) {
    $res2 = mysqli_query(
        $koneksi,
        "SELECT id,judul,foto_path,kategori,deadline,terkumpul,target_dana
        FROM kampanye WHERE pengelola_id=$user_id AND status='aktif' ORDER BY created_at DESC"
    );
    while ($r = mysqli_fetch_assoc($res2)) $my_camps[] = $r;
}

$kat_list = ['Bencana' => 'Bencana Alam', 'Pendidikan' => 'Pendidikan', 'Kesehatan' => 'Kesehatan', 'FasilitasUmum' => 'Fasilitas Umum'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — DonasiKu</title>
    <link rel="stylesheet" href="../style/global.css">
    <link rel="stylesheet" href="../style/main.css">
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

        <!-- KAMPANYE SAYA (pengelola) -->
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
            <div class="search-bar">
                <input type="text" name="q" placeholder="Cari judul, kategori, lokasi, atau tanggal..."
                    value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Cari 🔍</button>
            </div>

            <!-- FILTER DROPDOWN -->
            <div class="filter-row">
                <!-- Filter Kategori -->
                <div class="filter-group">
                    <label for="filter-kategori">Kategori:</label>
                    <select name="kat" id="filter-kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kat_list as $k => $lbl): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= $kategori === $k ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Lokasi -->
                <div class="filter-group">
                    <label for="filter-lokasi">Lokasi:</label>
                    <select name="lok" id="filter-lokasi">
                        <option value="">Semua Lokasi</option>
                        <?php foreach ($lokasi_list as $lok): ?>
                            <option value="<?= htmlspecialchars($lok) ?>" <?= $lokasi === $lok ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lok) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Rentang Dana -->
                <div class="filter-group">
                    <label for="filter-rentang">Rentang Dana:</label>
                    <select name="ran" id="filter-rentang">
                        <option value="">Semua Rentang Dana</option>
                        <option value="r1" <?= $rentang === 'r1' ? 'selected' : '' ?>>
                            < Rp 1.000.000</option>
                        <option value="r2" <?= $rentang === 'r2' ? 'selected' : '' ?>>Rp 1.000.000 - 5.000.000</option>
                        <option value="r3" <?= $rentang === 'r3' ? 'selected' : '' ?>>Rp 5.000.000 - 10.000.000</option>
                        <option value="r4" <?= $rentang === 'r4' ? 'selected' : '' ?>>Rp 10.000.000 - 50.000.000</option>
                        <option value="r5" <?= $rentang === 'r5' ? 'selected' : '' ?>>Rp 50.000.000 - 100.000.000</option>
                        <option value="r6" <?= $rentang === 'r6' ? 'selected' : '' ?>>Rp 100.000.000 - 200.000.000</option>
                        <option value="r7" <?= $rentang === 'r7' ? 'selected' : '' ?>>> Rp 200.000.000</option>
                    </select>
                </div>
            </div>
        </form>
        <br>
        <h3 class="section-title">Temukan Kampanye <span style="font-size:13px;color:var(--gray-muted);font-weight:500;">(<?= $total ?> kampanye aktif)</span></h3>

        <!-- Kampanye card -->
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
                            <div class="prog-mini">
                                <div class="prog-mini-fill" style="width:<?= $p ?>%"></div>
                            </div>
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
                    <a href="main.php?pg=<?= $i ?>&q=<?= urlencode($search) ?>&kat=<?= urlencode($kategori) ?>&lok=<?= urlencode($lokasi) ?>&ran=<?= urlencode($rentang) ?>"
                        class="pg-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
    <footer class="footer">
        <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan.</p>
    </footer>
</body>

</html>