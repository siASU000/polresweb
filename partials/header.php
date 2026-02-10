<?php
// partials/header.php
require_once __DIR__ . '/helpers.php';

// --- PHP LOGIC (Tidak Berubah) ---
$hsRes = $conn->query("SELECT * FROM header_settings ORDER BY id ASC LIMIT 1");
$hs = $hsRes ? $hsRes->fetch_assoc() : [];

$logoPath      = $hs['logo_path'] ?? 'assets/Logo Polresta Padang.png';
$searchEnabled = (int)($hs['search_enabled'] ?? 1) === 1;
$callLabel     = $hs['call_center_label'] ?? 'CALL CENTER';
$callWa        = $hs['call_center_wa'] ?? '+62 811 6693 110';

$waLink = 'https://wa.me/628116693110';
$waNum  = preg_replace('/[^0-9]/', '', (string)$callWa);
if ($waNum !== '') {
  if (strpos($waNum, '0') === 0) $waNum = '62' . substr($waNum, 1);
  $waLink = 'https://wa.me/' . $waNum;
}

$menuRes = $conn->query("SELECT * FROM header_menu WHERE is_active=1 ORDER BY parent_id ASC, sort_order ASC, id ASC");
$rows = [];
if ($menuRes) {
  while ($r = $menuRes->fetch_assoc()) $rows[] = $r;
}

$byId = [];
$tree = [];
foreach ($rows as $r) {
  $r['children'] = [];
  $byId[(int)$r['id']] = $r;
}
foreach ($byId as $id => $item) {
  $pid = (int)($item['parent_id'] ?? 0);
  if ($pid && isset($byId[$pid])) {
    $byId[$pid]['children'][] = $item;
  } else {
    $tree[] = $item;
  }
}
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$segments = explode('/', $currentPath);
$current = end($segments);
// Remove .php extension for matching
$current = preg_replace('/\.php$/', '', $current);
if ($current === '' || $current === 'webandruy') $current = 'index';
?>

<style>
  /* ===== HEADER DESKTOP ===== */
  header {
    background: #1a1a1a;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
    width: 100%;
  }

  .header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px;
    max-width: 1200px;
    margin: 0 auto;
  }

  .logo {
    height: 50px;
    width: auto;
    object-fit: contain;
  }

  .nav-wrapper {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    gap: 20px;
  }

  nav ul li a {
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    text-transform: uppercase;
    transition: color 0.3s;
  }

  nav ul li a:hover, nav ul li a.active {
    color: #f1c40f;
  }

  .header-search {
    display: flex;
    align-items: center;
    background: #fff;
    border-radius: 4px;
    overflow: hidden;
    height: 35px;
  }
  .header-search input {
    border: none;
    padding: 0 10px;
    font-size: 13px;
    outline: none;
    width: 150px;
  }
  .header-search button {
    background: #2c3e50;
    color: #fff;
    border: none;
    padding: 0 10px;
    height: 100%;
    cursor: pointer;
    font-size: 12px;
  }

  .call-center {
    background: #f1c40f;
    color: #000;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    text-transform: uppercase;
    font-size: 13px;
    white-space: nowrap;
  }

  /* Hamburger (hidden on desktop) */
  .menu-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
    z-index: 1100;
    background: none;
    border: none;
    padding: 5px;
  }
  .menu-toggle span {
    width: 25px;
    height: 3px;
    background-color: #fff;
    border-radius: 2px;
    transition: all 0.3s ease;
  }

  /* ===== MOBILE SIDEBAR OVERLAY ===== */
  .mobile-sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  .mobile-sidebar-overlay.active {
    display: block;
    opacity: 1;
  }

  /* ===== MOBILE SIDEBAR ===== */
  .mobile-sidebar {
    position: fixed;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #fff;
    z-index: 1060;
    transition: left 0.35s ease;
    display: flex;
    flex-direction: column;
    box-shadow: 4px 0 20px rgba(0,0,0,0.2);
    overflow-y: auto;
  }
  .mobile-sidebar.active {
    left: 0;
  }

  /* Sidebar Header */
  .sidebar-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    background: #fff;
    border-bottom: 1px solid #eee;
  }
  .sidebar-logo {
    height: 45px;
    width: auto;
    object-fit: contain;
  }
  .sidebar-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #333;
    cursor: pointer;
    padding: 5px;
    line-height: 1;
    transition: color 0.2s;
  }
  .sidebar-close:hover {
    color: #e53e3e;
  }

  /* Sidebar Menu */
  .sidebar-menu {
    list-style: none;
    margin: 0;
    padding: 10px 0;
    flex: 1;
  }
  .sidebar-menu > li {
    border-bottom: 1px solid #f0f0f0;
  }
  .sidebar-menu > li > a,
  .sidebar-menu > li > .sidebar-dropdown-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    text-decoration: none;
    color: #1a1a1a;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: background 0.2s, color 0.2s;
    cursor: pointer;
    background: none;
    border: none;
    width: 100%;
    font-family: inherit;
  }
  .sidebar-menu > li > a:hover,
  .sidebar-menu > li > .sidebar-dropdown-toggle:hover {
    background: #f7f7f7;
    color: #1a56db;
  }
  .sidebar-menu > li > a.active {
    color: #1a56db;
    background: #eff6ff;
    border-left: 4px solid #1a56db;
  }

  /* Dropdown chevron */
  .sidebar-chevron {
    font-size: 12px;
    transition: transform 0.3s ease;
    color: #999;
  }
  .sidebar-dropdown-toggle.open .sidebar-chevron {
    transform: rotate(180deg);
  }

  /* Sidebar sub-menu */
  .sidebar-submenu {
    list-style: none;
    margin: 0;
    padding: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease;
    background: #fafafa;
  }
  .sidebar-submenu.open {
    max-height: 500px;
  }
  .sidebar-submenu li a {
    display: block;
    padding: 10px 20px 10px 40px;
    text-decoration: none;
    color: #555;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
  }
  .sidebar-submenu li a:hover {
    background: #eef2ff;
    color: #1a56db;
  }

  /* Emergency Button */
  .sidebar-emergency {
    padding: 16px 20px 24px;
  }
  .sidebar-emergency a {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px;
    background: #dc2626;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 50px;
    letter-spacing: 1px;
    transition: background 0.2s, transform 0.2s;
    box-shadow: 0 4px 14px rgba(220,38,38,0.3);
  }
  .sidebar-emergency a:hover {
    background: #b91c1c;
    transform: scale(1.02);
  }
  .sidebar-emergency a svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 900px) {
    .menu-toggle {
      display: flex;
    }
    .nav-wrapper {
      display: none !important;
    }
  }

  @media (min-width: 901px) {
    .mobile-sidebar,
    .mobile-sidebar-overlay {
      display: none !important;
    }
  }
</style>

<header>
  <div class="header-container">
    <img src="<?= e($logoPath) ?>" alt="Polresta Padang Logo" class="logo">

    <button class="menu-toggle" id="mobile-menu-btn" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="nav-wrapper" id="nav-wrapper">
        <nav>
          <ul>
            <?php foreach ($tree as $m): ?>
              <?php
                $label = (string)$m['label'];
                $url   = (string)$m['url'];
                $urlPage = preg_replace('/\.php$/', '', basename(parse_url($url, PHP_URL_PATH) ?: ''));
                $isActive = ($current !== '' && $urlPage === $current);
                $hasDrop  = !empty($m['children']);
              ?>
              <li class="<?= $hasDrop ? 'has-dropdown' : '' ?>">
                <a href="<?= e($url) ?>" <?= $isActive ? 'class="active"' : '' ?>>
                  <?= e($label) ?>
                </a>

                <?php if ($hasDrop): ?>
                  <ul class="dropdown">
                    <?php foreach ($m['children'] as $c): ?>
                      <li><a href="<?= e((string)$c['url']) ?>"><?= e((string)$c['label']) ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </nav>

        <?php if ($searchEnabled): ?>
          <div class="header-search">
            <input type="text" placeholder="Cari Berita..." />
            <button type="button" onclick="window.location.href='berita'">Search</button>
          </div>
        <?php endif; ?>

        <button class="call-center" type="button" onclick="window.location.href='<?= e($waLink) ?>'">
          <?= e($callLabel) ?>
        </button>
    </div>
  </div>
</header>

<!-- MOBILE SIDEBAR -->
<div class="mobile-sidebar-overlay" id="sidebarOverlay"></div>
<aside class="mobile-sidebar" id="mobileSidebar">
  <div class="sidebar-top">
    <img src="<?= e($logoPath) ?>" alt="Logo" class="sidebar-logo">
    <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">&times;</button>
  </div>

  <ul class="sidebar-menu">
    <?php foreach ($tree as $m): ?>
      <?php
        $label = (string)$m['label'];
        $url   = (string)$m['url'];
        $urlPage = preg_replace('/\.php$/', '', basename(parse_url($url, PHP_URL_PATH) ?: ''));
        $isActive = ($current !== '' && $urlPage === $current);
        $hasDrop  = !empty($m['children']);
      ?>
      <?php if ($hasDrop): ?>
        <li>
          <button class="sidebar-dropdown-toggle" data-target="submenu-<?= (int)$m['id'] ?>">
            <?= e($label) ?>
            <span class="sidebar-chevron">&#9660;</span>
          </button>
          <ul class="sidebar-submenu" id="submenu-<?= (int)$m['id'] ?>">
            <?php foreach ($m['children'] as $c): ?>
              <li><a href="<?= e((string)$c['url']) ?>"><?= e((string)$c['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      <?php else: ?>
        <li>
          <a href="<?= e($url) ?>" <?= $isActive ? 'class="active"' : '' ?>>
            <?= e($label) ?>
          </a>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>

  <div class="sidebar-emergency">
    <a href="tel:110">
      <svg viewBox="0 0 24 24"><path d="M6.62 10.79a15.053 15.053 0 0 0 6.59 6.59l2.2-2.2a1.003 1.003 0 0 1 1.01-.24c1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
      EMERGENCY 110
    </a>
  </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    menuBtn.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Dropdown toggles
    document.querySelectorAll('.sidebar-dropdown-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var submenu = document.getElementById(targetId);
            var isOpen = submenu.classList.contains('open');

            // Close all other submenus
            document.querySelectorAll('.sidebar-submenu').forEach(function(s) {
                s.classList.remove('open');
            });
            document.querySelectorAll('.sidebar-dropdown-toggle').forEach(function(b) {
                b.classList.remove('open');
            });

            if (!isOpen) {
                submenu.classList.add('open');
                this.classList.add('open');
            }
        });
    });
});
</script>