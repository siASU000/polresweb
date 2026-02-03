<?php
// partials/header.php
require_once __DIR__ . '/helpers.php';

// ambil settings
$hsRes = $conn->query("SELECT * FROM header_settings ORDER BY id ASC LIMIT 1");
$hs = $hsRes ? $hsRes->fetch_assoc() : [];

$logoPath      = $hs['logo_path'] ?? 'assets/Logo Polresta Padang.png';
$searchEnabled = (int)($hs['search_enabled'] ?? 1) === 1;
$callLabel     = $hs['call_center_label'] ?? 'CALL CENTER';
$callWa        = $hs['call_center_wa'] ?? '+62 811 6693 110';

// wa link
$waLink = 'https://wa.me/628116693110';
$waNum  = preg_replace('/[^0-9]/', '', (string)$callWa);
if ($waNum !== '') {
  if (strpos($waNum, '0') === 0) $waNum = '62' . substr($waNum, 1);
  $waLink = 'https://wa.me/' . $waNum;
}

// ambil menu
$menuRes = $conn->query("SELECT * FROM header_menu WHERE is_active=1 ORDER BY parent_id ASC, sort_order ASC, id ASC");
$rows = [];
if ($menuRes) {
  while ($r = $menuRes->fetch_assoc()) $rows[] = $r;
}

// build tree
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

// helper active
$current = basename((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
?>
<header>
  <div class="header-container">
    <img src="<?= e($logoPath) ?>" alt="Polresta Padang Logo" class="logo">

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
      <div class="footer-search">
        <input type="text" placeholder="Cari Berita..." />
        <button type="button" onclick="window.location.href='berita.php'">Search</button>
      </div>
    <?php endif; ?>

    <button class="call-center" type="button" onclick="window.location.href='<?= e($waLink) ?>'">
      <?= e($callLabel) ?>
    </button>
  </div>
</header>
