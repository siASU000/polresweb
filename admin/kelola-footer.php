<?php
// admin/kelola-footer.php
declare(strict_types=1);

$ALLOWED_ROLES = ['admin','editor'];
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $contact = trim($_POST['contact_text'] ?? '');
  $fax = trim($_POST['fax_text'] ?? '');
  $copy = trim($_POST['copyright_text'] ?? '');
  $top = trim($_POST['top_bg'] ?? '#ef4444');
  $bottom = trim($_POST['bottom_bg'] ?? '#991b1b');

  if ($contact === '' || $copy === '') {
    $flash = ['type'=>'err','msg'=>'Contact text & copyright wajib diisi.'];
  } else {
    $stmt = $conn->prepare("UPDATE footer_settings SET contact_text=?, fax_text=?, copyright_text=?, top_bg=?, bottom_bg=? WHERE id=1");
    $stmt->bind_param("sssss", $contact, $fax, $copy, $top, $bottom);
    $stmt->execute();
    $stmt->close();
    $flash = ['type'=>'ok','msg'=>'Footer tersimpan.'];
  }
}

$fsRes = $conn->query("SELECT * FROM footer_settings WHERE id=1");
$fs = $fsRes ? $fsRes->fetch_assoc() : [];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Footer</title>
  <link rel="stylesheet" href="admin.css">
  <style>
    .wrap{max-width:900px;margin:24px auto;padding:0 16px}
    .card{background:#fff;border-radius:12px;padding:16px;margin-bottom:16px;border:1px solid #e5e7eb}
    label{font-weight:600;font-size:14px;display:block;margin:8px 0}
    input{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:10px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .btn{padding:10px 14px;border-radius:10px;border:0;cursor:pointer;background:#0b5ed7;color:#fff}
    .preview{border-radius:12px;overflow:hidden;border:1px solid #e5e7eb}
    .pTop{padding:22px 18px;color:#fff}
    .pBot{padding:14px 18px;color:#fff;text-align:center}
  </style>
</head>
<body>
  <div class="wrap">
    <h2>Kelola Footer</h2>

    <?php if ($flash): ?>
      <div class="card" style="border-color:<?= $flash['type']==='ok'?'#86efac':'#fecaca' ?>;">
        <?= e($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <form method="post">
        <label>Contact Text (bar merah atas)</label>
        <input name="contact_text" value="<?= e((string)($fs['contact_text'] ?? '')) ?>">

        <label>Fax Text</label>
        <input name="fax_text" value="<?= e((string)($fs['fax_text'] ?? '')) ?>">

        <label>Copyright Text (bar bawah)</label>
        <input name="copyright_text" value="<?= e((string)($fs['copyright_text'] ?? '')) ?>">

        <div class="row">
          <div>
            <label>Warna Bar Atas</label>
            <input name="top_bg" value="<?= e((string)($fs['top_bg'] ?? '#ef4444')) ?>">
          </div>
          <div>
            <label>Warna Bar Bawah</label>
            <input name="bottom_bg" value="<?= e((string)($fs['bottom_bg'] ?? '#991b1b')) ?>">
          </div>
        </div>

        <div style="margin-top:12px">
          <button class="btn" type="submit">Simpan Footer</button>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Preview</h3>
      <div class="preview">
        <div class="pTop" style="background:<?= e((string)($fs['top_bg'] ?? '#ef4444')) ?>">
          <div><?= e((string)($fs['contact_text'] ?? '')) ?></div>
          <div style="margin-top:8px"><?= e((string)($fs['fax_text'] ?? '')) ?></div>
        </div>
        <div class="pBot" style="background:<?= e((string)($fs['bottom_bg'] ?? '#991b1b')) ?>">
          <?= e((string)($fs['copyright_text'] ?? '')) ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
