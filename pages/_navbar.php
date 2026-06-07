<?php
/*
 * Shared Navbar Component
 * Include dengan: <?php include '_navbar.php'; ?>
 * Variabel yang perlu ada: $backUrl (string|null), $pageTitle (string|null)
 */
$backUrl   = $backUrl ?? null;
$pageTitle = $pageTitle ?? '';
$username  = $_SESSION['username'] ?? null;
$role      = $_SESSION['role'] ?? 'donatur';
$avatar    = $_SESSION['avatar'] ?? '../assets/avatar1.jpeg';
?>
<nav class="topnav">
    <!-- Kiri: Tombol kembali atau Brand -->
    <div class="topnav-left">
        <?php if ($backUrl): ?>
            <a href="<?= $backUrl ?>" class="btn-back-nav" title="Kembali">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </a>
        <?php else: ?>
            <a href="<?= ($role === 'pengelola') ? 'kelola_kampanye.php' : 'main.php' ?>"
                class="navbar-brand-inline">
                <div class="brand-icon-sm">💚</div>
                <span class="brand-name-sm">Donasi<em>Ku</em></span>
            </a>
        <?php endif; ?>
        <?php if ($pageTitle): ?>
            <span class="nav-page-title"><?= htmlspecialchars($pageTitle) ?></span>
        <?php endif; ?>
    </div>

    <!-- Kanan: Avatar + dropdown -->
    <div class="topnav-right">
        <?php if ($username): ?>
            <!-- [FIX 8] Tampilkan nama user secara eksplisit di navbar -->
            <span class="nav-greeting">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? $username) ?></span>
            <div class="avatar-wrap" id="avatarWrap">
                <img src="<?= htmlspecialchars($avatar) ?>"
                    class="nav-avatar" alt="Profil"
                    onerror="this.src='../assets/avatar1.jpeg'">
                <div class="avatar-dropdown" id="avatarDropdown">
                    <div class="dropdown-user">
                        <img src="<?= htmlspecialchars($avatar) ?>" class="dd-avatar"
                            onerror="this.src='../assets/avatar1.jpeg'">
                        <div>
                            <div class="dd-name"><?= htmlspecialchars($username) ?></div>
                            <div class="dd-role"><?= ucfirst($role) ?></div>
                        </div>
                    </div>
                    <hr class="dd-divider">
                    <a href="profil.php" class="dd-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                        </svg>
                        Edit Profil
                    </a>
                    <?php if ($role === 'pengelola'): ?>
                        <a href="kelola_kampanye.php" class="dd-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                            </svg>
                            Kampanye Saya
                        </a>
                    <?php else: ?>
                        <a href="riwayat_donasi.php" class="dd-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            Riwayat Donasi
                        </a>
                    <?php endif; ?>
                    <hr class="dd-divider">
                    <a href="logout.php" class="dd-item dd-logout">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Keluar
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-masuk-nav">Masuk</a>
        <?php endif; ?>
    </div>
</nav>
    <link rel="stylesheet" href="../style/_navbar.css">

<script>
    (function() {
        const wrap = document.getElementById('avatarWrap');
        const dd = document.getElementById('avatarDropdown');
        if (!wrap || !dd) return;
        wrap.addEventListener('click', function(e) {
            e.stopPropagation();
            dd.classList.toggle('open');
        });
        document.addEventListener('click', function() {
            dd.classList.remove('open');
        });
    })();
</script>