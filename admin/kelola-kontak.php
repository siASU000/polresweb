<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// AUTH
$ALLOWED_ROLES = ['admin', 'editor'];
require __DIR__ . '/auth_guard.php';

// DB (root/db_connection.php)
require __DIR__ . '/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan db_connection.php membuat variabel \$conn (mysqli).");
}
$conn->set_charset("utf8mb4");

function e(string $v): string {
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// ambil settings (row pertama)
$res = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
$settings = $res ? $res->fetch_assoc() : null;

// kalau belum ada row, buat 1 row kosong
if (!$settings) {
  $conn->query("INSERT INTO contact_settings (address, phone, whatsapp, email, fax, op_hours, maps_embed)
                VALUES ('','','','','','','')");
  $res = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
  $settings = $res ? $res->fetch_assoc() : [];
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $address    = trim($_POST['address'] ?? '');
  $phone      = trim($_POST['phone'] ?? '');
  $whatsapp   = trim($_POST['whatsapp'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $fax        = trim($_POST['fax'] ?? '');
  $op_hours   = trim($_POST['op_hours'] ?? '');
  $maps_embed = trim($_POST['maps_embed'] ?? '');

  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $flash = ['type' => 'danger', 'msg' => 'Format email tidak valid.'];
  } else {
    $id = (int)($settings['id'] ?? 0);

    $stmt = $conn->prepare("
      UPDATE contact_settings
      SET address=?, phone=?, whatsapp=?, email=?, fax=?, op_hours=?, maps_embed=?
      WHERE id=?
    ");
    $stmt->bind_param("sssssssi", $address, $phone, $whatsapp, $email, $fax, $op_hours, $maps_embed, $id);
    $ok = $stmt->execute();
    $stmt->close();

    $flash = $ok
      ? ['type' => 'success', 'msg' => 'Kontak berhasil disimpan.']
      : ['type' => 'danger', 'msg' => 'Gagal menyimpan. ' . $conn->error];

    $res = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
    $settings = $res ? $res->fetch_assoc() : $settings;
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Kontak (Hubungi Kami)</title>
  <link rel="stylesheet" href="admin.css">
  <style>
    .wrap{max-width:1100px;margin:30px auto;padding:0 16px}
    .card{background:#fff;border-radius:12px;padding:18px 18px 22px;border:1px solid #e8e8e8}
    .row{display:flex;gap:16px;flex-wrap:wrap}
    .col{flex:1;min-width:260px}
    label{display:block;font-weight:600;margin:10px 0 6px}
    input,textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;outline:none}
    textarea{min-height:110px;resize:vertical}
    .btn{background:#f5b400;border:none;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer}
    .alert{padding:10px 12px;border-radius:10px;margin-bottom:12px}
    .success{background:#e9fff0;border:1px solid #b7f2c8}
    .danger{background:#fff0f0;border:1px solid #ffbcbc}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Kelola Kontak (Hubungi Kami)</h1>

    <?php if ($flash): ?>
      <div class="alert <?= e($flash['type']) === 'success' ? 'success' : 'danger' ?>">
        <?= e($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <form method="post">
        <label>Alamat</label>
        <textarea name="address" placeholder="Alamat lengkap..."><?= e((string)($settings['address'] ?? '')) ?></textarea>

        <div class="row">
          <div class="col">
            <label>Telepon</label>
            <input name="phone" value="<?= e((string)($settings['phone'] ?? '')) ?>" placeholder="110 / 0751xxxxxx">
          </div>
          <div class="col">
            <label>WhatsApp</label>
            <input name="whatsapp" value="<?= e((string)($settings['whatsapp'] ?? '')) ?>" placeholder="+62811xxxxxxx">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label>Email</label>
            <input type="email" name="email" value="<?= e((string)($settings['email'] ?? '')) ?>" placeholder="contoh@email.com">
          </div>
          <div class="col">
            <label>Fax</label>
            <input name="fax" value="<?= e((string)($settings['fax'] ?? '')) ?>" placeholder="(0751) 33724">
          </div>
        </div>

        <label>Jam Operasional</label>
        <textarea name="op_hours" placeholder="Contoh: Senin–Jumat 08.00–16.00"><?= e((string)($settings['op_hours'] ?? '')) ?></textarea>

        <label>Google Maps Embed (iframe / URL embed)</label>
        <textarea name="maps_embed" placeholder="Paste iframe embed atau URL embed..."><?= e((string)($settings['maps_embed'] ?? '')) ?></textarea>

        <div style="margin-top:12px">
          <button class="btn" type="submit">SIMPAN</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
