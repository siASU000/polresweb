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
$current = basename((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
?>

<style>
  /* Reset dasar untuk header */
  header {
    background: #1a1a1a; /* Sesuaikan warna background header Anda */
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

  /* Logo */
  .logo {
    height: 50px; /* Tinggi fix agar tidak gepeng */
    width: auto;
    object-fit: contain;
  }

  /* Wadah Navigasi & Action (Search/Button) */
  .nav-wrapper {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  /* Menu Desktop */
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
    color: #f1c40f; /* Warna kuning emas */
  }

  /* Search Box */
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

  /* Call Center Button */
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

  /* Hamburger Menu (Hidden on Desktop) */
  .menu-toggle {
    display: none;
    flex-direction: column;
    cursor: pointer;
    gap: 5px;
  }
  .menu-toggle span {
    width: 25px;
    height: 3px;
    background-color: #fff;
    border-radius: 2px;
  }

  /* --- RESPONSIVE MOBILE (Max-width 768px) --- */
  @media (max-width: 900px) {
    .menu-toggle {
      display: flex; /* Munculkan hamburger */
    }

    .nav-wrapper {
      position: absolute;
      top: 100%;
      left: 0;
      width: 100%;
      background: #222; /* Background menu dropdown hp */
      flex-direction: column; /* Susun ke bawah */
      align-items: flex-start;
      padding: 20px;
      gap: 15px;
      display: none; /* Sembunyikan defaultnya */
      box-shadow: 0 5px 10px rgba(0,0,0,0.2);
    }

    .nav-wrapper.active {
      display: flex; /* Munculkan saat class active ditambahkan JS */
    }

    nav ul {
      flex-direction: column;
      width: 100%;
      gap: 15px;
    }

    nav ul li a {
      display: block;
      padding: 5px 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      width: 100%;
    }

    .header-search {
      width: 100%; /* Search full width di HP */
    }
    .header-search input {
      width: 100%;
    }

    .call-center {
      width: 100%;
      text-align: center;
    }
    
    /* Dropdown adjustment for mobile */
    .has-dropdown .dropdown {
        position: static;
        box-shadow: none;
        background: transparent;
        padding-left: 15px;
        display: none; /* Default hide sub-menu */
    }
    .has-dropdown:hover .dropdown {
        display: block; /* Show on hover/tap */
    }
  }
</style>

<header>
  <div class="header-container">
    <img src="<?= e($logoPath) ?>" alt="Polresta Padang Logo" class="logo">

    <div class="menu-toggle" id="mobile-menu-btn">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <div class="nav-wrapper" id="nav-wrapper">
        <nav>
          <ul>
            <?php foreach ($tree as $m): ?>
              <?php
                $label = (string)$m['label'];
                $url   = (string)$m['url'];
                $isActive = ($current !== '' && basename($url) === $current);
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
            <button type="button" onclick="window.location.href='berita.php'">Search</button>
          </div>
        <?php endif; ?>

        <button class="call-center" type="button" onclick="window.location.href='<?= e($waLink) ?>'">
          <?= e($callLabel) ?>
        </button>
    </div>
  </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const navWrapper = document.getElementById('nav-wrapper');

        menuBtn.addEventListener('click', function() {
            navWrapper.classList.toggle('active');
        });
    });
</script>