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
                    <polyline points="15 18 9 12 15 6"/>
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
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        Edit Profil
                    </a>
                    <?php if ($role === 'pengelola'): ?>
                    <a href="kelola_kampanye.php" class="dd-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Kampanye Saya
                    </a>
                    <?php else: ?>
                    <a href="riwayat_donasi.php" class="dd-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/><polyline points="12 6 12 12 16 14"/></svg>
                        Riwayat Donasi
                    </a>
                    <?php endif; ?>
                    <hr class="dd-divider">
                    <a href="logout.php" class="dd-item dd-logout">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Keluar
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-masuk-nav">Masuk</a>
        <?php endif; ?>
    </div>
</nav>

<style>
.topnav {
    position: sticky; top: 0; z-index: 200;
    background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-darker) 100%);
    height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.2);
}
.topnav-left { display:flex; align-items:center; gap:10px; }
.topnav-right { display:flex; align-items:center; }

.btn-back-nav {
    width:38px; height:38px;
    background: rgba(255,255,255,0.14);
    border: 1.5px solid rgba(255,255,255,0.35);
    border-radius: 10px;
    display:flex; align-items:center; justify-content:center;
    color: white; text-decoration:none;
    transition: var(--transition);
}
.btn-back-nav:hover { background:rgba(255,255,255,0.25); }

.nav-page-title {
    color: rgba(255,255,255,0.9);
    font-family: 'Montserrat', sans-serif;
    font-weight: 700; font-size: 15px;
}

.navbar-brand-inline {
    display:flex; align-items:center; gap:8px;
    text-decoration:none; color:white;
}
.brand-icon-sm {
    width:30px; height:30px;
    background:rgba(255,255,255,0.15);
    border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:16px;
}
.brand-name-sm {
    font-family:'Montserrat',sans-serif;
    font-weight:800; font-size:18px;
    color:white;
}
.brand-name-sm em { color:#7FFFA8; font-style:normal; }

/* Avatar + Dropdown */
.avatar-wrap { position:relative; }
.nav-avatar {
    width:36px; height:36px;
    border-radius:50%;
    border:2px solid rgba(255,255,255,0.55);
    object-fit:cover; cursor:pointer;
    transition: var(--transition);
    display:block;
}
.nav-avatar:hover { border-color:white; transform:scale(1.06); }

.avatar-dropdown {
    display:none; position:absolute;
    top:calc(100% + 10px); right:0;
    background:white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    min-width:210px;
    padding:8px 0;
    z-index:300;
    animation: fadeInUp 0.2s ease;
}
.avatar-dropdown.open { display:block; }

.dropdown-user {
    display:flex; align-items:center; gap:10px;
    padding:12px 16px 10px;
}
.dd-avatar {
    width:38px; height:38px; border-radius:50%;
    object-fit:cover; flex-shrink:0;
    border:2px solid var(--green-light);
}
.dd-name { font-weight:700; font-size:13px; color:#1a2319; }
.dd-role { font-size:11px; color:var(--green-primary); font-weight:600; text-transform:uppercase; letter-spacing:0.4px; }

.dd-divider { border:none; border-top:1px solid var(--gray-border); margin:4px 0; }

.dd-item {
    display:flex; align-items:center; gap:9px;
    padding:9px 16px;
    color:var(--gray-text);
    font-size:13px; font-weight:600;
    text-decoration:none;
    transition: var(--transition);
}
.dd-item:hover { background:var(--green-light); color:var(--green-primary); }
.dd-logout { color:#dc2626; }
.dd-logout:hover { background:#fee2e2; color:#dc2626; }

.btn-masuk-nav {
    background:rgba(255,255,255,0.18);
    border:1.5px solid rgba(255,255,255,0.45);
    color:white; border-radius:30px;
    padding:7px 18px; font-size:13px; font-weight:700;
    text-decoration:none; transition:var(--transition);
}
.btn-masuk-nav:hover { background:rgba(255,255,255,0.28); }
</style>

<script>
(function(){
    const wrap = document.getElementById('avatarWrap');
    const dd   = document.getElementById('avatarDropdown');
    if (!wrap || !dd) return;
    wrap.addEventListener('click', function(e){
        e.stopPropagation();
        dd.classList.toggle('open');
    });
    document.addEventListener('click', function(){
        dd.classList.remove('open');
    });
})();
</script>
