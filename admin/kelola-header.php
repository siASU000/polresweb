<?php
// admin/kelola-header.php
declare(strict_types=1);

$ALLOWED_ROLES = ['admin', 'editor'];
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

function e(string $v): string
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

$flash = null;

// save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
  $logo = trim($_POST['logo_path'] ?? 'assets/Logo Polresta Padang.png');
  $search = isset($_POST['search_enabled']) ? 1 : 0;
  $label = trim($_POST['call_center_label'] ?? 'CALL CENTER');
  $wa = trim($_POST['call_center_wa'] ?? '+62 811 6693 110');
  $tagline = trim($_POST['tagline'] ?? '');

  $stmt = $conn->prepare("UPDATE header_settings SET logo_path=?, search_enabled=?, call_center_label=?, call_center_wa=?, tagline=? WHERE id=1");
  $stmt->bind_param("sisss", $logo, $search, $label, $wa, $tagline);
  $stmt->execute();
  $stmt->close();

  $flash = ['type' => 'ok', 'msg' => 'Header settings tersimpan.'];
}

// add menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_menu') {
  $parent = (int) ($_POST['parent_id'] ?? 0);
  $parentId = $parent > 0 ? $parent : null;

  $label = trim($_POST['label'] ?? '');
  $url = trim($_POST['url'] ?? '');
  $sort = (int) ($_POST['sort_order'] ?? 0);

  if ($label === '' || $url === '') {
    $flash = ['type' => 'err', 'msg' => 'Label & URL wajib diisi.'];
  } else {
    $stmt = $conn->prepare("INSERT INTO header_menu (parent_id,label,url,sort_order,is_active) VALUES (?,?,?,?,1)");
    // bind_param tidak bisa langsung nullable int, jadi pakai trick:
    if ($parentId === null) {
      $null = null;
      $stmt->bind_param("isss", $null, $label, $url, $sort); // ini bakal warning
    }
  }
}

// INSERT nullable yang aman:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_menu') {
  $parent = (int) ($_POST['parent_id'] ?? 0);
  $label = trim($_POST['label'] ?? '');
  $url = trim($_POST['url'] ?? '');
  $sort = (int) ($_POST['sort_order'] ?? 0);

  if ($label !== '' && $url !== '') {
    if ($parent > 0) {
      $stmt = $conn->prepare("INSERT INTO header_menu (parent_id,label,url,sort_order,is_active) VALUES (?,?,?,?,1)");
      $stmt->bind_param("issi", $parent, $label, $url, $sort);
    } else {
      $stmt = $conn->prepare("INSERT INTO header_menu (parent_id,label,url,sort_order,is_active) VALUES (NULL,?,?,?,1)");
      $stmt->bind_param("ssi", $label, $url, $sort);
    }
    $stmt->execute();
    $stmt->close();
    $flash = ['type' => 'ok', 'msg' => 'Menu ditambahkan.'];
  } elseif (($_POST['action'] ?? '') === 'add_menu') {
    $flash = ['type' => 'err', 'msg' => 'Label & URL wajib diisi.'];
  }
}

// delete menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_menu') {
  $id = (int) ($_POST['id'] ?? 0);
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM header_menu WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $flash = ['type' => 'ok', 'msg' => 'Menu dihapus.'];
  }
}

// toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_menu') {
  $id = (int) ($_POST['id'] ?? 0);
  $val = (int) ($_POST['val'] ?? 1);
  if ($id > 0) {
    $stmt = $conn->prepare("UPDATE header_menu SET is_active=? WHERE id=?");
    $stmt->bind_param("ii", $val, $id);
    $stmt->execute();
    $stmt->close();
    $flash = ['type' => 'ok', 'msg' => 'Status menu diperbarui.'];
  }
}

$hsRes = $conn->query("SELECT * FROM header_settings WHERE id=1");
$hs = $hsRes ? $hsRes->fetch_assoc() : ['logo_path' => 'assets/Logo Polresta Padang.png', 'search_enabled' => 1, 'call_center_label' => 'CALL CENTER', 'call_center_wa' => '+62 811 6693 110'];

$menuRes = $conn->query("SELECT m.*, p.label AS parent_label
  FROM header_menu m
  LEFT JOIN header_menu p ON p.id=m.parent_id
  ORDER BY COALESCE(m.parent_id, m.id), m.parent_id ASC, m.sort_order ASC, m.id ASC");
$menus = [];
if ($menuRes)
  while ($r = $menuRes->fetch_assoc())
    $menus[] = $r;

$parentsRes = $conn->query("SELECT id,label FROM header_menu WHERE parent_id IS NULL ORDER BY sort_order ASC, id ASC");
$parents = [];
if ($parentsRes)
  while ($r = $parentsRes->fetch_assoc())
    $parents[] = $r;
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Header</title>
  <link rel="stylesheet" href="admin.css">
  <style>
    .wrap {
      max-width: 1100px;
      margin: 24px auto;
      padding: 0 16px
    }

    .card {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
      border: 1px solid #e5e7eb
    }

    .row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px
    }

    label {
      font-weight: 600;
      font-size: 14px
    }

    input,
    select {
      width: 100%;
      padding: 10px;
      border: 1px solid #d1d5db;
      border-radius: 10px
    }

    table {
      width: 100%;
      border-collapse: collapse
    }

    th,
    td {
      border-bottom: 1px solid #e5e7eb;
      padding: 10px;
      text-align: left;
      font-size: 14px;
      vertical-align: top
    }

    .btn {
      padding: 10px 14px;
      border-radius: 10px;
      border: 0;
      cursor: pointer
    }

    .btn-ok {
      background: #0b5ed7;
      color: #fff
    }

    .btn-danger {
      background: #b91c1c;
      color: #fff
    }

    .pill {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px
    }

    .pill-on {
      background: #dcfce7;
      color: #166534
    }

    .pill-off {
      background: #fee2e2;
      color: #991b1b
    }
  </style>
</head>

<body>
  <div class="wrap">
    <h2>Kelola Header</h2>

    <?php if ($flash): ?>
      <div class="card" style="border-color:<?= $flash['type'] === 'ok' ? '#86efac' : '#fecaca' ?>;">
        <?= e($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h3>Header Settings</h3>
      <form method="post">
        <input type="hidden" name="action" value="save_settings">
        <div class="row">
          <div>
            <label>Logo Path</label>
            <input name="logo_path" value="<?= e((string) $hs['logo_path']) ?>">
          </div>
          <div>
            <label>Call Center Label</label>
            <input name="call_center_label" value="<?= e((string) $hs['call_center_label']) ?>">
          </div>
          <div>
            <label>Call Center WhatsApp</label>
            <input name="call_center_wa" value="<?= e((string) $hs['call_center_wa']) ?>">
          </div>
          <div>
            <label>Tagline (Muncul di Berita)</label>
            <input name="tagline" value="<?= e((string) ($hs['tagline'] ?? '')) ?>"
              placeholder="Contoh: Melayani dengan Sepenuh Hati">
          </div>
          <div style="display:flex;align-items:end;gap:10px">
            <label style="display:flex;align-items:center;gap:8px;font-weight:600">
              <input type="checkbox" name="search_enabled" <?= ((int) $hs['search_enabled'] === 1) ? 'checked' : '' ?>
                style="width:auto">
              Aktifkan Search
            </label>
          </div>
        </div>
        <div style="margin-top:12px">
          <button class="btn btn-ok" type="submit">Simpan Settings</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Tambah Menu</h3>
      <form method="post">
        <input type="hidden" name="action" value="add_menu">
        <div class="row">
          <div>
            <label>Parent (dropdown)</label>
            <select name="parent_id">
              <option value="0">(Menu Utama)</option>
              <?php foreach ($parents as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= e($p['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>Urutan</label>
            <input type="number" name="sort_order" value="0">
          </div>
          <div>
            <label>Label</label>
            <input name="label" placeholder="HOME / PROFIL / ...">
          </div>
          <div>
            <label>URL</label>
            <input name="url" placeholder="index.php / profil.php#sejarah / ...">
          </div>
        </div>
        <div style="margin-top:12px">
          <button class="btn btn-ok" type="submit">Tambah</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Daftar Menu</h3>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Parent</th>
            <th>Label</th>
            <th>URL</th>
            <th>Urutan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menus as $m): ?>
            <tr>
              <td><?= (int) $m['id'] ?></td>
              <td><?= e((string) ($m['parent_label'] ?? '—')) ?></td>
              <td><?= e((string) $m['label']) ?></td>
              <td><?= e((string) $m['url']) ?></td>
              <td><?= (int) $m['sort_order'] ?></td>
              <td>
                <?php if ((int) $m['is_active'] === 1): ?>
                  <span class="pill pill-on">aktif</span>
                <?php else: ?>
                  <span class="pill pill-off">nonaktif</span>
                <?php endif; ?>
              </td>
              <td style="display:flex;gap:8px;flex-wrap:wrap">
                <form method="post">
                  <input type="hidden" name="action" value="toggle_menu">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <input type="hidden" name="val" value="<?= ((int) $m['is_active'] === 1) ? 0 : 1 ?>">
                  <button class="btn btn-ok"
                    type="submit"><?= ((int) $m['is_active'] === 1) ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
                <form method="post" onsubmit="return confirm('Hapus menu ini?')">
                  <input type="hidden" name="action" value="delete_menu">
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <button class="btn btn-danger" type="submit">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($menus)): ?>
            <tr>
              <td colspan="7">Belum ada menu.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>

</html>