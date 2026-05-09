<?php
session_start();
include 'koneksi.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: pages/login.php");
    exit();
}

$username = $_SESSION['username'];

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Contoh query delete - ganti dengan query DB asli
    // $query = "DELETE FROM kampanye WHERE id = $id AND penggalang = '$username'";
    // mysqli_query($koneksi, $query);
    header("Location: pengelolakampanye.php?msg=deleted");
    exit();
}

// Pesan flash
$flash = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') $flash = 'Kampanye berhasil dihapus.';
    if ($_GET['msg'] === 'updated') $flash = 'Kampanye berhasil diperbarui.';
    if ($_GET['msg'] === 'created') $flash = 'Kampanye baru berhasil dibuat!';
}

// Data dummy kampanye (ganti dengan query DB asli)
$campaigns = [
    [
        'id' => 1,
        'img' => 'assets/contoh2.jpg',
        'judul' => 'Longsor di Desa Awikwok',
        'tag' => 'Bencana',
        'deadline' => '18 Juli 2026',
        'terkumpul' => 3500000,
        'target' => 50000000,
        'donatur' => 24,
        'status' => 'aktif',
    ],
    [
        'id' => 2,
        'img' => 'assets/contoh1.jpg',
        'judul' => 'Bencana Banjir Aceh Sumatra Utara',
        'tag' => 'Bencana',
        'deadline' => '27 Juni 2026',
        'terkumpul' => 243276519,
        'target' => 500000000,
        'donatur' => 182,
        'status' => 'aktif',
    ],
    [
        'id' => 3,
        'img' => 'assets/contoh3.jpg',
        'judul' => 'Beasiswa Anak Kurang Mampu Jawa Tengah',
        'tag' => 'Pendidikan',
        'deadline' => '01 September 2026',
        'terkumpul' => 12000000,
        'target' => 100000000,
        'donatur' => 48,
        'status' => 'aktif',
    ],
];

function rupiah($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function pct($t, $d) {
    if ($d <= 0) return 0;
    return min(100, round(($t / $d) * 100));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kampanye — DonasiKu</title>
    <link rel="stylesheet" href="style/global.css">
    <style>
        body { background: var(--gray-bg); }

        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 24px 70px;
        }

        /* ---- PAGE HEADER ---- */
        .page-header {
            background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-darker) 100%);
            border-radius: var(--radius-xl);
            padding: 36px 40px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
            animation: fadeInUp 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            top: -100px; right: -60px;
        }
        .page-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 26px;
            font-weight: 900;
            font-style: italic;
            line-height: 1.2;
            position: relative;
            z-index: 1;
        }
        .page-header h1 span { color: #7FFFA8; }
        .page-header p {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 6px;
            position: relative;
            z-index: 1;
        }
        .header-left { z-index: 1; }

        /* ---- FLASH ---- */
        .flash {
            background: var(--green-light);
            border-left: 4px solid var(--green-primary);
            border-radius: var(--radius-sm);
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
            color: var(--green-dark);
            margin-bottom: 20px;
            animation: fadeInUp 0.3s ease;
        }

        /* ---- STATS ROW ---- */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: var(--radius-md);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
            animation: fadeInUp 0.45s ease;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-icon.green { background: var(--green-light); }
        .stat-icon.blue { background: #e0f0ff; }
        .stat-icon.yellow { background: #fff8e0; }
        .stat-body .label {
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .stat-body .value {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: #1a2319;
        }

        /* ---- TOOLBAR ---- */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .toolbar h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 17px;
            font-weight: 800;
            color: var(--green-primary);
        }
        .toolbar-right { display:flex; gap:10px; flex-wrap:wrap; }

        .search-mini {
            display: flex;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }
        .search-mini input {
            padding: 8px 14px;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            background: white;
            outline: none;
            color: #333;
            min-width: 200px;
        }
        .search-mini button {
            background: var(--green-primary);
            border: none;
            padding: 0 14px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            transition: var(--transition);
        }
        .search-mini button:hover { background: var(--green-dark); }

        /* ---- CAMPAIGN CARDS GRID ---- */
        .camps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .camp-card {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            animation: fadeInUp 0.5s ease;
        }
        .camp-card:hover { box-shadow: var(--shadow-md); transform: translateY(-3px); }

        .camp-img-wrap {
            position: relative;
            height: 160px;
        }
        .camp-img-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            filter: brightness(0.78);
        }
        .camp-img-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.05) 40%, rgba(0,0,0,0.5) 100%);
        }

        .camp-status {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 1;
        }

        .camp-tag {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 1;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.45);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .camp-deadline {
            position: absolute;
            bottom: 10px;
            left: 12px;
            z-index: 1;
            color: rgba(255,255,255,0.85);
            font-size: 11px;
            font-weight: 600;
        }

        .camp-body { padding: 16px 18px 18px; }
        .camp-body h3 {
            font-weight: 700;
            font-size: 14px;
            color: #1a2319;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .camp-progress-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }
        .camp-progress-row .terkumpul {
            font-size: 14px;
            font-weight: 800;
            color: var(--green-primary);
        }
        .camp-progress-row .pct {
            font-size: 12px;
            font-weight: 700;
            color: var(--gray-muted);
        }

        .camp-meta-row {
            display: flex;
            gap: 16px;
            margin-top: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .camp-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            color: var(--gray-muted);
            font-weight: 600;
        }

        .camp-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1.5px solid var(--gray-border);
        }
        .camp-actions a, .camp-actions button {
            flex: 1;
            text-align: center;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            font-family: 'Montserrat', sans-serif;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: var(--transition);
        }
        .act-view { background: var(--green-light); color: var(--green-primary); }
        .act-view:hover { background: var(--green-primary); color: white; }
        .act-edit { background: #e0f0ff; color: #2563eb; }
        .act-edit:hover { background: #2563eb; color: white; }
        .act-delete { background: #fee2e2; color: #dc2626; }
        .act-delete:hover { background: #dc2626; color: white; }

        /* ---- EMPTY STATE ---- */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-muted);
            animation: fadeIn 0.5s ease;
        }
        .empty-state .es-icon { font-size: 56px; margin-bottom: 16px; }
        .empty-state h3 { font-size: 18px; font-weight: 700; color: #1a2319; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; margin-bottom: 20px; }

        /* ---- MODAL ---- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 300;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 32px 36px;
            max-width: 580px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.3s ease;
        }
        .modal-box h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--green-primary);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1.5px solid var(--gray-border);
        }
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1.5px solid var(--gray-border);
        }

        /* CONFIRM MODAL */
        .confirm-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 36px 40px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.3s ease;
        }
        .confirm-box .ci { font-size: 48px; margin-bottom: 14px; }
        .confirm-box h3 { font-size: 18px; font-weight: 800; color: #1a2319; margin-bottom: 8px; }
        .confirm-box p { font-size: 14px; color: var(--gray-muted); margin-bottom: 22px; }
        .confirm-box .cb-row { display:flex; gap:12px; justify-content:center; }

        /* FORM IN MODAL */
        .mfg { margin-bottom: 16px; }
        .mfg label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--green-primary);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 6px;
        }
        .mfg input, .mfg select, .mfg textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            background: #f9faf9;
            color: #333;
            outline: none;
            transition: var(--transition);
        }
        .mfg input:focus, .mfg select:focus, .mfg textarea:focus {
            border-color: var(--green-primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(45,138,82,0.10);
        }
        .mfg textarea { resize:vertical; min-height:90px; }
        .mfg-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

        /* PAYMENT METHOD CHECKBOXES */
        .payment-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pay-check {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #f9faf9;
            border: 1.5px solid var(--gray-border);
            border-radius: var(--radius-sm);
            padding: 8px 14px;
            cursor: pointer;
            transition: var(--transition);
        }
        .pay-check:has(input:checked),
        .pay-check.selected {
            border-color: var(--green-primary);
            background: var(--green-light);
            color: var(--green-primary);
        }
        .pay-check input { accent-color: var(--green-primary); }
        .pay-check span { font-size: 13px; font-weight: 600; }

        /* PHOTO UPLOAD */
        .photo-drop {
            border: 2px dashed var(--gray-border);
            border-radius: var(--radius-sm);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray-muted);
            font-size: 13px;
        }
        .photo-drop:hover { border-color: var(--green-primary); color: var(--green-primary); }
        .photo-drop input { display: none; }

        @media (max-width:640px) {
            .mfg-row { grid-template-columns:1fr; }
            .page-header { flex-direction:column; gap:16px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="pages/main.php" class="navbar-brand">
        <div class="brand-icon">💚</div>
        <div class="brand-name">Donasi<span>Ku</span></div>
    </a>
    <div class="navbar-links">
        <a href="pages/main.php">Beranda</a>
        <a href="pengelolakampanye.php" class="active">Kampanye Saya</a>
        <a href="pages/pengajuanDonasi.php">Donasi</a>
        <img src="assets/avatar1.jpeg" class="navbar-avatar" alt="<?= htmlspecialchars($username) ?>"
             title="<?= htmlspecialchars($username) ?>">
    </div>
</nav>

<div class="page-wrap">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="header-left">
            <h1>Kelola <span>Kampanye</span> Anda</h1>
            <p>Pantau, edit, dan kelola semua kampanye donasi yang telah Anda buat.</p>
        </div>
        <button class="btn btn-ghost btn-lg" onclick="openModal('createModal')">
            + Buat Kampanye Baru
        </button>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
    <div class="flash">✓ <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- SUMMARY STATS -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon green">📋</div>
            <div class="stat-body">
                <div class="label">Total Kampanye</div>
                <div class="value"><?= count($campaigns) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-body">
                <div class="label">Total Terkumpul</div>
                <div class="value" style="font-size:16px"><?= rupiah(array_sum(array_column($campaigns, 'terkumpul'))) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-body">
                <div class="label">Total Donatur</div>
                <div class="value"><?= array_sum(array_column($campaigns, 'donatur')) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">✅</div>
            <div class="stat-body">
                <div class="label">Kampanye Aktif</div>
                <div class="value"><?= count(array_filter($campaigns, fn($c) => $c['status'] === 'aktif')) ?></div>
            </div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
        <h2>Daftar Kampanye</h2>
        <div class="toolbar-right">
            <div class="search-mini">
                <input type="text" id="campSearch" placeholder="Cari kampanye..." oninput="filterCamps()">
                <button>🔍</button>
            </div>
            <select style="padding:8px 14px; border:1.5px solid var(--gray-border); border-radius:var(--radius-sm); font-family:'Poppins',sans-serif; font-size:13px; background:white; outline:none; color:#444;" onchange="filterByTag(this.value)">
                <option value="">Semua Kategori</option>
                <option value="Bencana">Bencana</option>
                <option value="Pendidikan">Pendidikan</option>
                <option value="Kesehatan">Kesehatan</option>
                <option value="Fasilitas Umum">Fasilitas Umum</option>
            </select>
        </div>
    </div>

    <!-- CAMPAIGNS GRID -->
    <?php if (empty($campaigns)): ?>
    <div class="empty-state">
        <div class="es-icon">📭</div>
        <h3>Belum Ada Kampanye</h3>
        <p>Anda belum membuat kampanye donasi. Mulai sekarang!</p>
        <button class="btn btn-primary" onclick="openModal('createModal')">+ Buat Kampanye Pertama</button>
    </div>
    <?php else: ?>

    <div class="camps-grid" id="campsGrid">
        <?php foreach ($campaigns as $c): ?>
        <?php $p = pct($c['terkumpul'], $c['target']); ?>
        <div class="camp-card" data-title="<?= strtolower($c['judul']) ?>" data-tag="<?= $c['tag'] ?>">
            <div class="camp-img-wrap">
                <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['judul']) ?>">
                <div class="camp-tag">#<?= htmlspecialchars($c['tag']) ?></div>
                <div class="camp-status">
                    <span class="badge <?= $c['status'] === 'aktif' ? 'badge-green' : 'badge-gray' ?>">
                        <?= $c['status'] === 'aktif' ? '● Aktif' : 'Selesai' ?>
                    </span>
                </div>
                <div class="camp-deadline">📅 <?= htmlspecialchars($c['deadline']) ?></div>
            </div>
            <div class="camp-body">
                <h3><?= htmlspecialchars($c['judul']) ?></h3>

                <div class="camp-progress-row">
                    <span class="terkumpul"><?= rupiah($c['terkumpul']) ?></span>
                    <span class="pct"><?= $p ?>%</span>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width:<?= $p ?>%"></div>
                </div>

                <div class="camp-meta-row">
                    <div class="camp-meta-item">🎯 <?= rupiah($c['target']) ?></div>
                    <div class="camp-meta-item">👥 <?= $c['donatur'] ?> donatur</div>
                </div>

                <div class="camp-actions">
                    <a href="pages/detailDonasi.php?id=<?= $c['id'] ?>" class="act-view">Lihat</a>
                    <button class="act-edit"
                            onclick="openEdit(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['judul'])) ?>', '<?= $c['tag'] ?>', '<?= $c['target'] ?>', '<?= $c['deadline'] ?>')">
                        Edit
                    </button>
                    <button class="act-delete" onclick="confirmDelete(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['judul'])) ?>')">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

</div><!-- end page-wrap -->

<!-- ========== MODAL: BUAT KAMPANYE ========== -->
<div class="modal-overlay" id="createModal">
    <div class="modal-box">
        <h2>+ Buat Kampanye Baru</h2>

        <form action="pengelolakampanye.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">

            <div class="mfg">
                <label>Foto Kampanye</label>
                <label class="photo-drop" for="fotoKampanye">
                    <div style="font-size:28px; margin-bottom:6px;">🖼️</div>
                    <div>Klik untuk unggah foto</div>
                    <div style="font-size:11px; margin-top:4px; color:var(--gray-muted)">JPG, PNG — maks 5MB</div>
                    <input type="file" id="fotoKampanye" name="foto" accept="image/*" onchange="previewPhoto(this)">
                </label>
                <img id="photoPreview" style="display:none; width:100%; max-height:160px; object-fit:cover; border-radius:var(--radius-sm); margin-top:8px;" alt="preview">
            </div>

            <div class="mfg-row">
                <div class="mfg">
                    <label>Nominal Target (Rp)</label>
                    <input type="number" name="target" placeholder="cth. 50000000" required>
                </div>
                <div class="mfg">
                    <label>Penggalang Dana</label>
                    <input type="text" name="penggalang" value="<?= htmlspecialchars($username) ?>" readonly>
                </div>
            </div>

            <div class="mfg">
                <label>Judul Donasi</label>
                <input type="text" name="judul" placeholder="Judul kampanye yang menarik..." required>
            </div>

            <div class="mfg-row">
                <div class="mfg">
                    <label>Kategori / Tag</label>
                    <select name="tag" required>
                        <option value="">Pilih kategori</option>
                        <option value="Bencana">Bencana Alam</option>
                        <option value="Pendidikan">Pendidikan</option>
                        <option value="Kesehatan">Kesehatan</option>
                        <option value="Fasilitas Umum">Fasilitas Umum</option>
                    </select>
                </div>
                <div class="mfg">
                    <label>Batas Waktu</label>
                    <input type="date" name="deadline" required>
                </div>
            </div>

            <div class="mfg">
                <label>Cerita / Deskripsi</label>
                <textarea name="cerita" placeholder="Ceritakan kondisi yang dihadapi dan mengapa donasi ini penting..." required></textarea>
            </div>

            <div class="mfg">
                <label>Metode Pencairan Dana</label>
                <div class="payment-checks">
                    <label class="pay-check">
                        <input type="checkbox" name="metode[]" value="QRIS">
                        <span>QRIS</span>
                    </label>
                    <label class="pay-check">
                        <input type="checkbox" name="metode[]" value="Rekening">
                        <span>Rekening</span>
                    </label>
                    <label class="pay-check">
                        <input type="checkbox" name="metode[]" value="E-Wallet">
                        <span>E-Wallet</span>
                    </label>
                    <label class="pay-check">
                        <input type="checkbox" name="metode[]" value="BTC">
                        <span>BTC</span>
                    </label>
                    <label class="pay-check">
                        <input type="checkbox" name="metode[]" value="Credit Card">
                        <span>Credit Card</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('createModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Buat Kampanye</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL: EDIT KAMPANYE ========== -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h2>✏️ Edit Kampanye</h2>

        <form action="pengelolakampanye.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">

            <div class="mfg">
                <label>Judul Donasi</label>
                <input type="text" name="judul" id="editJudul" required>
            </div>

            <div class="mfg-row">
                <div class="mfg">
                    <label>Kategori / Tag</label>
                    <select name="tag" id="editTag">
                        <option value="Bencana">Bencana Alam</option>
                        <option value="Pendidikan">Pendidikan</option>
                        <option value="Kesehatan">Kesehatan</option>
                        <option value="Fasilitas Umum">Fasilitas Umum</option>
                    </select>
                </div>
                <div class="mfg">
                    <label>Target Dana (Rp)</label>
                    <input type="number" name="target" id="editTarget">
                </div>
            </div>

            <div class="mfg">
                <label>Batas Waktu</label>
                <input type="date" name="deadline" id="editDeadline">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL: KONFIRMASI HAPUS ========== -->
<div class="modal-overlay" id="deleteModal">
    <div class="confirm-box">
        <div class="ci">🗑️</div>
        <h3>Hapus Kampanye?</h3>
        <p id="deleteConfirmText">Kampanye ini akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
        <div class="cb-row">
            <button class="btn btn-outline" onclick="closeModal('deleteModal')">Batal</button>
            <a id="deleteLink" href="#" class="btn btn-danger">Ya, Hapus</a>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 <strong>DonasiKu</strong> — moses hervian listyan. Semua hak dilindungi.</p>
</footer>

<script>
// ---- MODAL HELPERS ----
function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});

// ---- EDIT ----
function openEdit(id, judul, tag, target, deadline) {
    document.getElementById('editId').value = id;
    document.getElementById('editJudul').value = judul;
    document.getElementById('editTag').value = tag;
    document.getElementById('editTarget').value = target;
    document.getElementById('editDeadline').value = deadline;
    openModal('editModal');
}

// ---- DELETE CONFIRM ----
function confirmDelete(id, judul) {
    document.getElementById('deleteConfirmText').textContent =
        `"${judul}" akan dihapus secara permanen dan tidak dapat dikembalikan.`;
    document.getElementById('deleteLink').href = `pengelolakampanye.php?action=delete&id=${id}`;
    openModal('deleteModal');
}

// ---- SEARCH ----
function filterCamps() {
    const q = document.getElementById('campSearch').value.toLowerCase();
    document.querySelectorAll('.camp-card').forEach(card => {
        const title = card.dataset.title || '';
        card.style.display = title.includes(q) ? '' : 'none';
    });
}

// ---- FILTER BY TAG ----
function filterByTag(tag) {
    document.querySelectorAll('.camp-card').forEach(card => {
        if (!tag || card.dataset.tag === tag) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// ---- PHOTO PREVIEW ----
function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ---- PROGRESS ANIMATION ----
document.querySelectorAll('.progress-bar-fill').forEach(bar => {
    const target = bar.style.width;
    bar.style.width = '0%';
    setTimeout(() => { bar.style.width = target; }, 200);
});

// ESC key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
    }
});
</script>

</body>
</html>
