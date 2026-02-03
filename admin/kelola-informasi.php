<?php
declare(strict_types=1);

$ALLOWED_ROLES = ['admin','editor'];
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid.");
}
$conn->set_charset('utf8mb4');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$uploadDir = __DIR__ . '/../uploads/informasi/';
$uploadBase = 'uploads/informasi/';

if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0777, true);
}

$flash = "";
$err = "";

/** Helpers upload */
function saveUpload(array $file, string $uploadDir): array {
  if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return ['ok' => true, 'name' => null];
  }

  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'msg' => 'Upload gagal.'];
  }

  $max = 4 * 1024 * 1024; // 4MB
  if (($file['size'] ?? 0) > $max) {
    return ['ok' => false, 'msg' => 'Ukuran file maksimal 4MB.'];
  }

  $tmp = $file['tmp_name'] ?? '';
  if (!is_file($tmp)) {
    return ['ok' => false, 'msg' => 'File upload tidak valid.'];
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) {
    return ['ok' => false, 'msg' => 'Format gambar harus JPG/PNG/WEBP.'];
  }

  $ext = $allowed[$mime];
  $safeName = 'info_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $dest = $uploadDir . $safeName;

  if (!move_uploaded_file($tmp, $dest)) {
    return ['ok' => false, 'msg' => 'Gagal memindahkan file.'];
  }

  return ['ok' => true, 'name' => $safeName];
}

/** Hapus 1 item */
if (($_POST['action'] ?? '') === 'delete') {
  $id = (int)($_POST['id'] ?? 0);

  // Ambil gambar dulu
  $stmt = $conn->prepare("SELECT gambar FROM informasi WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if ($row && !empty($row['gambar'])) {
    $path = __DIR__ . '/../' . $uploadBase . $row['gambar'];
    if (is_file($path)) @unlink($path);
  }

  $stmt = $conn->prepare("DELETE FROM informasi WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();

  $flash = "Data berhasil dihapus.";
}

/** Update urutan massal */
if (($_POST['action'] ?? '') === 'save_order') {
  $orders = $_POST['urutan'] ?? [];
  if (is_array($orders)) {
    $stmt = $conn->prepare("UPDATE informasi SET urutan=? WHERE id=?");
    foreach ($orders as $idStr => $urutStr) {
      $id = (int)$idStr;
      $urut = (int)$urutStr;
      $stmt->bind_param("ii", $urut, $id);
      $stmt->execute();
    }
    $stmt->close();
    $flash = "Urutan berhasil diperbarui.";
  }
}

/** Toggle aktif/nonaktif */
if (($_POST['action'] ?? '') === 'toggle') {
  $id = (int)($_POST['id'] ?? 0);
  $to = (int)($_POST['to'] ?? 0);
  $stmt = $conn->prepare("UPDATE informasi SET is_active=? WHERE id=?");
  $stmt->bind_param("ii", $to, $id);
  $stmt->execute();
  $stmt->close();
  $flash = "Status berhasil diperbarui.";
}

/** Add new */
if (($_POST['action'] ?? '') === 'add') {
  $kategori = trim((string)($_POST['kategori'] ?? 'dpo'));
  if (!in_array($kategori, ['dpo','orang_hilang','lainnya'], true)) $kategori = 'dpo';

  $nama = trim((string)($_POST['nama'] ?? ''));
  $subjudul = trim((string)($_POST['subjudul'] ?? ''));
  $terakhir = trim((string)($_POST['terakhir_terlihat'] ?? ''));
  $tombol_text = trim((string)($_POST['tombol_text'] ?? ''));
  $tombol_url = trim((string)($_POST['tombol_url'] ?? ''));
  $tipe = trim((string)($_POST['tipe'] ?? ''));
  $judul = trim((string)($_POST['judul'] ?? ''));
  $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
  $is_active = !empty($_POST['is_active']) ? 1 : 0;
  $urutan = (int)($_POST['urutan'] ?? 0);

  // rules minimum
  if ($kategori === 'lainnya') {
    if ($judul === '') $err = "Judul wajib diisi untuk Informasi Lainnya.";
    if (!in_array($tipe, ['himbauan','pengumuman','layanan'], true)) $tipe = 'himbauan';
  } else {
    if ($nama === '') $err = "Nama wajib diisi untuk DPO / Orang Hilang.";
    if ($tombol_text === '') $tombol_text = ($kategori === 'dpo') ? 'Laporkan' : 'Hubungi';
  }

  $upload = saveUpload($_FILES['gambar'] ?? [], $uploadDir);
  if (!$upload['ok']) $err = $upload['msg'];

  if ($err === '') {
    $gambar = $upload['name'];

    $stmt = $conn->prepare("
      INSERT INTO informasi
      (kategori, nama, subjudul, terakhir_terlihat, tombol_text, tombol_url, tipe, judul, deskripsi, gambar, is_active, urutan)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
      "ssssssssssii",
      $kategori, $nama, $subjudul, $terakhir, $tombol_text, $tombol_url,
      $tipe, $judul, $deskripsi, $gambar, $is_active, $urutan
    );
    $stmt->execute();
    $stmt->close();

    $flash = "Data berhasil ditambahkan.";
  }
}

/** Load list */
$list = [];
$rs = $conn->query("SELECT * FROM informasi ORDER BY kategori ASC, urutan ASC, created_at DESC, id DESC");
if ($rs) {
  while ($row = $rs->fetch_assoc()) $list[] = $row;
  $rs->free();
}

function badgeKategori(string $k): string {
  return match ($k) {
    'dpo' => 'DPO',
    'orang_hilang' => 'Orang Hilang',
    default => 'Informasi Lainnya'
  };
}
function badgeTipe(?string $t): string {
  return match ($t) {
    'himbauan' => 'HIMBAUAN',
    'pengumuman' => 'PENGUMUMAN',
    'layanan' => 'LAYANAN',
    default => '-'
  };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kelola Informasi</title>
  <style>
    body{font-family:system-ui,Arial; margin:0; background:#f6f7fb;}
    .wrap{max-width:1100px; margin:24px auto; padding:0 16px;}
    h1{margin:0 0 12px;}
    .topbar{display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px;}
    .btn{display:inline-block; padding:10px 14px; border-radius:10px; border:1px solid #ddd; background:#fff; text-decoration:none; color:#111; font-weight:600;}
    .btn.primary{background:#0b5cff; color:#fff; border-color:#0b5cff;}
    .grid{display:grid; grid-template-columns:1fr 1fr; gap:16px;}
    .card{background:#fff; border:1px solid #e8e8e8; border-radius:14px; padding:16px;}
    label{display:block; font-size:13px; color:#444; margin:10px 0 6px;}
    input, select, textarea{width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; outline:none;}
    textarea{min-height:90px; resize:vertical;}
    .row{display:grid; grid-template-columns:1fr 1fr; gap:10px;}
    .msg{padding:12px 14px; border-radius:12px; margin:10px 0 14px;}
    .ok{background:#e9fff2; border:1px solid #b9f2cf;}
    .bad{background:#ffecec; border:1px solid #ffbdbd;}
    table{width:100%; border-collapse:collapse;}
    th,td{padding:10px 8px; border-bottom:1px solid #eee; text-align:left; vertical-align:top;}
    th{font-size:12px; color:#555;}
    .pill{display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700;}
    .pill.on{background:#e7fff0; color:#0a7a36; border:1px solid #b9f2cf;}
    .pill.off{background:#fff3e7; color:#8b4a06; border:1px solid #ffd2a8;}
    .thumb{width:86px; height:56px; object-fit:cover; border-radius:10px; border:1px solid #eee; background:#fafafa;}
    .actions{display:flex; gap:8px; flex-wrap:wrap;}
    .btn-sm{padding:8px 10px; border-radius:10px; border:1px solid #ddd; background:#fff; font-weight:700; cursor:pointer;}
    .danger{background:#b60000; color:#fff; border-color:#b60000;}
    .muted{color:#666; font-size:12px;}
    @media(max-width:900px){ .grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Kelola Informasi</h1>
    <div class="topbar">
      <a class="btn" href="dashboard.php">← Kembali</a>
      <a class="btn primary" href="/polresta_padang/informasi.php" target="_blank">Lihat Informasi Publik</a>
    </div>

    <?php if ($flash !== ""): ?>
      <div class="msg ok"><?= h($flash) ?></div>
    <?php endif; ?>
    <?php if ($err !== ""): ?>
      <div class="msg bad"><?= h($err) ?></div>
    <?php endif; ?>

    <div class="grid">
      <div class="card">
        <h3 style="margin:0 0 6px;">Tambah Data Informasi</h3>
        <div class="muted">DPO & Orang Hilang tampil sebagai kartu. Informasi Lainnya tampil sebagai tabel.</div>

        <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
          <input type="hidden" name="action" value="add"/>

          <label>Kategori</label>
          <select name="kategori" id="kategoriSelect" onchange="toggleFields()">
            <option value="dpo">DPO</option>
            <option value="orang_hilang">Orang Hilang</option>
            <option value="lainnya">Informasi Lainnya</option>
          </select>

          <div id="cardFields">
            <label>Nama *</label>
            <input name="nama" placeholder="Contoh: Andi (alias ...)" />

            <label id="subjudulLabel">Kasus / Ciri-ciri</label>
            <input name="subjudul" placeholder="DPO: Kasus... / Orang Hilang: Ciri-ciri..." />

            <label>Terakhir terlihat</label>
            <input name="terakhir_terlihat" placeholder="Contoh: Padang, 12 Jan 2026" />

            <div class="row">
              <div>
                <label>Teks Tombol</label>
                <input name="tombol_text" placeholder="Laporkan / Hubungi" />
              </div>
              <div>
                <label>URL Tombol</label>
                <input name="tombol_url" placeholder="Contoh: https://wa.me/62811xxxx" />
              </div>
            </div>
          </div>

          <div id="lainnyaFields" style="display:none;">
            <label>Tipe</label>
            <select name="tipe">
              <option value="himbauan">Himbauan</option>
              <option value="pengumuman">Pengumuman</option>
              <option value="layanan">Layanan</option>
            </select>

            <label>Judul *</label>
            <input name="judul" placeholder="Contoh: Waspada penipuan..." />
          </div>

          <label>Deskripsi (opsional)</label>
          <textarea name="deskripsi" placeholder="Isi deskripsi singkat..."></textarea>

          <label>Gambar (JPG/PNG/WEBP, maks 4MB)</label>
          <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" />

          <div class="row">
            <div>
              <label>Urutan</label>
              <input type="number" name="urutan" value="0" />
            </div>
            <div style="display:flex; align-items:center; gap:10px; padding-top:30px;">
              <input type="checkbox" name="is_active" value="1" checked style="width:auto;">
              <span style="font-weight:700;">Tampilkan (aktif)</span>
            </div>
          </div>

          <div style="margin-top:14px;">
            <button class="btn primary" type="submit">Simpan</button>
          </div>
        </form>
      </div>

      <div class="card">
        <form method="POST">
          <input type="hidden" name="action" value="save_order"/>
          <h3 style="margin:0 0 10px;">Daftar Informasi</h3>

          <div class="muted" style="margin-bottom:10px;">
            Tips: urutan kecil tampil lebih dulu. Status <b>Aktif</b> saja yang tampil di publik.
          </div>

          <table>
            <thead>
              <tr>
                <th>Gambar</th>
                <th>Info</th>
                <th>Status</th>
                <th>Urutan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($list) === 0): ?>
                <tr><td colspan="5" class="muted">Belum ada data.</td></tr>
              <?php else: ?>
                <?php foreach ($list as $it): ?>
                  <?php
                    $id = (int)$it['id'];
                    $kategori = (string)$it['kategori'];
                    $isActive = (int)$it['is_active'] === 1;
                    $urut = (int)$it['urutan'];

                    $thumb = '';
                    if (!empty($it['gambar'])) $thumb = $uploadBase . $it['gambar'];

                    // Teks ringkas
                    if ($kategori === 'lainnya') {
                      $title = (string)($it['judul'] ?? '');
                      $sub = badgeTipe($it['tipe'] ?? null);
                      $desc = (string)($it['deskripsi'] ?? '');
                    } else {
                      $title = (string)($it['nama'] ?? '');
                      $sub = (string)($it['subjudul'] ?? '');
                      $desc = (string)($it['terakhir_terlihat'] ?? '');
                    }
                  ?>
                  <tr>
                    <td>
                      <?php if ($thumb !== '' && is_file(__DIR__ . '/../' . $thumb)): ?>
                        <img class="thumb" src="<?= h('../'.$thumb) ?>" alt="thumb">
                      <?php else: ?>
                        <div class="thumb"></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="font-weight:800;"><?= h($title !== '' ? $title : '(tanpa judul)') ?></div>
                      <div class="muted"><?= h(badgeKategori($kategori)) ?><?= $kategori==='lainnya' ? ' • '.h($sub) : '' ?></div>
                      <?php if ($sub !== '' && $kategori !== 'lainnya'): ?>
                        <div class="muted">Kasus/Ciri: <?= h($sub) ?></div>
                      <?php endif; ?>
                      <?php if ($desc !== ''): ?>
                        <div class="muted"><?= $kategori==='lainnya' ? h($desc) : 'Terakhir terlihat: '.h($desc) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($it['tombol_url'])): ?>
                        <div class="muted">Link: <?= h((string)$it['tombol_url']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="pill <?= $isActive ? 'on' : 'off' ?>"><?= $isActive ? 'AKTIF' : 'NONAKTIF' ?></span>
                      <form method="POST" style="margin-top:8px;">
                        <input type="hidden" name="action" value="toggle"/>
                        <input type="hidden" name="id" value="<?= $id ?>"/>
                        <input type="hidden" name="to" value="<?= $isActive ? 0 : 1 ?>"/>
                        <button class="btn-sm" type="submit"><?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                      </form>
                    </td>
                    <td>
                      <input type="number" name="urutan[<?= $id ?>]" value="<?= $urut ?>" style="max-width:90px;">
                    </td>
                    <td>
                      <div class="actions">
                        <form method="POST" onsubmit="return confirm('Hapus data ini?');">
                          <input type="hidden" name="action" value="delete"/>
                          <input type="hidden" name="id" value="<?= $id ?>"/>
                          <button class="btn-sm danger" type="submit">Hapus</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>

          <div style="margin-top:12px;">
            <button class="btn primary" type="submit">Simpan Urutan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<script>
function toggleFields(){
  const kat = document.getElementById('kategoriSelect').value;
  const card = document.getElementById('cardFields');
  const lain = document.getElementById('lainnyaFields');
  const subLabel = document.getElementById('subjudulLabel');

  if(kat === 'lainnya'){
    card.style.display = 'none';
    lain.style.display = 'block';
  } else {
    card.style.display = 'block';
    lain.style.display = 'none';
    subLabel.textContent = (kat === 'dpo') ? 'Kasus' : 'Ciri-ciri';
  }
}
toggleFields();
</script>

</body>
</html>
