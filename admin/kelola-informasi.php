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

/** LOGIC PHP TETAP SAMA (TIDAK ADA YANG DIHAPUS) **/
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
  $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
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

/** Toggle aktif */
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

  if ($kategori === 'lainnya') {
    if ($judul === '') $err = "Judul wajib diisi.";
    if (!in_array($tipe, ['himbauan','pengumuman','layanan'], true)) $tipe = 'himbauan';
  } else {
    if ($nama === '') $err = "Nama wajib diisi.";
    if ($tombol_text === '') $tombol_text = ($kategori === 'dpo') ? 'Laporkan' : 'Hubungi';
  }

  $upload = saveUpload($_FILES['gambar'] ?? [], $uploadDir);
  if (!$upload['ok']) $err = $upload['msg'];
  if ($err === '') {
    $gambar = $upload['name'];
    $stmt = $conn->prepare("INSERT INTO informasi (kategori, nama, subjudul, terakhir_terlihat, tombol_text, tombol_url, tipe, judul, deskripsi, gambar, is_active, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssii", $kategori, $nama, $subjudul, $terakhir, $tombol_text, $tombol_url, $tipe, $judul, $deskripsi, $gambar, $is_active, $urutan);
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
  <title>Kelola Informasi - Polresta Padang</title>
  <style>
    :root {
      --primary: #0b5cff;
      --primary-hover: #084ed4;
      --bg: #f8fafc;
      --card-bg: #ffffff;
      --text-main: #1e293b;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --radius: 12px;
      --danger: #ef4444;
      --success: #22c55e;
      --warning: #f59e0b;
    }
    
    * { box-sizing: border-box; }
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text-main); margin: 0; line-height: 1.5; }
    
    .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
    
    .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    h1 { font-size: 24px; font-weight: 800; margin: 0; color: #0f172a; }
    
    .topbar { display: flex; gap: 12px; }
    .btn { cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 10px 18px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card-bg); color: var(--text-main); font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s; gap: 8px; }
    .btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .btn.primary { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn.primary:hover { background: var(--primary-hover); }
    .btn.danger { background: #fff1f2; color: var(--danger); border-color: #fecdd3; }
    .btn.danger:hover { background: #ffe4e6; }
    
    .grid { display: grid; grid-template-columns: 400px 1fr; gap: 24px; align-items: start; }
    .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 24px; }
    
    .form-group { margin-bottom: 16px; }
    label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: var(--text-main); }
    input, select, textarea { width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; transition: border 0.2s; background: #fcfcfd; }
    input:focus, select:focus, textarea:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(11, 92, 255, 0.1); }
    
    .msg { padding: 14px; border-radius: var(--radius); margin-bottom: 20px; font-size: 14px; font-weight: 500; }
    .ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .bad { background: #fef2f2; color: #991b1b; border: 1px solid #fecdd3; }
    
    table { width: 100%; border-collapse: separate; border-spacing: 0; }
    th { background: #f1f5f9; padding: 12px 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); text-align: left; border-bottom: 1px solid var(--border); }
    td { padding: 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    
    .thumb-container { position: relative; width: 80px; height: 60px; border-radius: 8px; overflow: hidden; background: #f1f5f9; border: 1px solid var(--border); }
    .thumb-img { width: 100%; height: 100%; object-fit: cover; }
    
    .pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.02em; }
    .pill.on { background: #dcfce7; color: #166534; }
    .pill.off { background: #fee2e2; color: #991b1b; }
    
    .info-title { font-weight: 700; font-size: 15px; color: #0f172a; margin-bottom: 2px; }
    .info-meta-sub { font-size: 12px; color: var(--text-muted); display: flex; gap: 8px; align-items: center; }
    
    .btn-action-sm { padding: 6px 12px; font-size: 12px; font-weight: 700; border-radius: 8px; }
    
    @media (max-width: 1024px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="header-section">
      <div>
        <h1>Kelola Informasi</h1>
        <p style="color:var(--text-muted); margin: 5px 0 0;">Polresta Padang - Panel Manajemen Konten</p>
      </div>
      <div class="topbar">
        <a class="btn" href="dashboard.php">Dashboard</a>
        <a class="btn primary" href="/webandruy/informasi" target="_blank">Lihat Publik</a>
      </div>
    </div>

    <?php if ($flash): ?> <div class="msg ok"><?= h($flash) ?></div> <?php endif; ?>
    <?php if ($err): ?> <div class="msg bad"><?= h($err) ?></div> <?php endif; ?>

    <div class="grid">
      <div class="card">
        <h3 style="margin-top:0; margin-bottom:8px;">Input Data Baru</h3>
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">Silakan pilih kategori terlebih dahulu untuk menyesuaikan formulir.</p>

        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add"/>

          <div class="form-group">
            <label>Pilih Kategori</label>
            <select name="kategori" id="kategoriSelect" onchange="toggleFields()">
              <option value="dpo">DPO (Daftar Pencarian Orang)</option>
              <option value="orang_hilang">Orang Hilang</option>
              <option value="lainnya">Informasi Lainnya (Himbauan/Layanan)</option>
            </select>
          </div>

          <div id="cardFields">
            <div class="form-group">
              <label>Nama Lengkap / Inisial *</label>
              <input name="nama" placeholder="Masukkan nama..." />
            </div>
            <div class="form-group">
              <label id="subjudulLabel">Kasus / Ciri-ciri</label>
              <input name="subjudul" placeholder="Detail kasus atau ciri fisik..." />
            </div>
            <div class="form-group">
              <label>Lokasi Terakhir Terlihat</label>
              <input name="terakhir_terlihat" placeholder="Contoh: Kec. Padang Barat..." />
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
              <div class="form-group">
                <label>Teks Tombol</label>
                <input name="tombol_text" placeholder="Laporkan" />
              </div>
              <div class="form-group">
                <label>URL / Link</label>
                <input name="tombol_url" placeholder="https://..." />
              </div>
            </div>
          </div>

          <div id="lainnyaFields" style="display:none;">
            <div class="form-group">
              <label>Tipe Konten</label>
              <select name="tipe">
                <option value="himbauan">Himbauan Kamtibmas</option>
                <option value="pengumuman">Pengumuman Resmi</option>
                <option value="layanan">Informasi Layanan</option>
              </select>
            </div>
            <div class="form-group">
              <label>Judul Informasi *</label>
              <input name="judul" placeholder="Contoh: Waspada Curanmor..." />
            </div>
          </div>

          <div class="form-group">
            <label>Deskripsi Detail</label>
            <textarea name="deskripsi" placeholder="Tambahkan informasi tambahan jika diperlukan..."></textarea>
          </div>

          <div class="form-group">
            <label>Lampiran Gambar</label>
            <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" />
            <small style="color:var(--text-muted); font-size:11px;">Maksimal 4MB (Format: JPG, PNG, WEBP)</small>
          </div>

          <div style="display:grid; grid-template-columns: 100px 1fr; gap: 20px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <div class="form-group" style="margin:0;">
              <label>Urutan</label>
              <input type="number" name="urutan" value="0" />
            </div>
            <label style="margin:0; cursor:pointer; display:flex; align-items:center; gap:8px;">
              <input type="checkbox" name="is_active" value="1" checked style="width:18px; height:18px; margin:0;">
              <span>Aktifkan Postingan</span>
            </label>
          </div>

          <button class="btn primary" type="submit" style="width:100%; padding:14px;">Simpan Data Informasi</button>
        </form>
      </div>

      <div class="card" style="padding:0; overflow:hidden;">
        <form method="POST">
          <input type="hidden" name="action" value="save_order"/>
          <div style="padding: 24px 24px 10px;">
            <h3 style="margin:0;">Daftar Konten</h3>
            <p style="font-size:13px; color:var(--text-muted);">Total data tersimpan: <b><?= count($list) ?></b></p>
          </div>

          <div style="overflow-x:auto;">
            <table>
              <thead>
                <tr>
                  <th width="100">Gambar</th>
                  <th>Detail Informasi</th>
                  <th width="120">Status</th>
                  <th width="100">Urutan</th>
                  <th width="80">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($list) === 0): ?>
                  <tr><td colspan="5" style="text-align:center; padding:40px; color:var(--text-muted);">Belum ada data informasi.</td></tr>
                <?php else: ?>
                  <?php foreach ($list as $it): 
                    $id = (int)$it['id'];
                    $kategori = (string)$it['kategori'];
                    $isActive = (int)$it['is_active'] === 1;
                    $thumb = (!empty($it['gambar'])) ? $uploadBase . $it['gambar'] : '';
                    
                    if ($kategori === 'lainnya') {
                      $title = $it['judul'];
                      $meta = badgeKategori($kategori) . ' • ' . badgeTipe($it['tipe']);
                    } else {
                      $title = $it['nama'];
                      $meta = badgeKategori($kategori) . ' • ' . $it['subjudul'];
                    }
                  ?>
                  <tr>
                    <td>
                      <div class="thumb-container">
                        <?php if ($thumb && is_file(__DIR__ . '/../' . $thumb)): ?>
                          <img class="thumb-img" src="<?= h('../'.$thumb) ?>">
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <div class="info-title"><?= h($title ?: '(Tanpa Judul)') ?></div>
                      <div class="info-meta-sub"><?= h($meta) ?></div>
                    </td>
                    <td>
                      <span class="pill <?= $isActive ? 'on' : 'off' ?>"><?= $isActive ? '● AKTIF' : '● NONAKTIF' ?></span>
                      <div style="margin-top:8px;">
                        <button type="button" class="btn btn-action-sm" onclick="submitToggle(<?= $id ?>, <?= $isActive ? 0 : 1 ?>)">
                          <?= $isActive ? 'Matikan' : 'Hidupkan' ?>
                        </button>
                      </div>
                    </td>
                    <td>
                      <input type="number" name="urutan[<?= $id ?>]" value="<?= (int)$it['urutan'] ?>" style="padding: 6px; text-align:center;">
                    </td>
                    <td>
                      <button type="button" class="btn danger btn-action-sm" onclick="confirmDelete(<?= $id ?>)">Hapus</button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <div style="padding: 20px; background: #f1f5f9; text-align: right;">
            <button class="btn primary" type="submit">Perbarui Urutan Data</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <form id="toggleForm" method="POST"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" id="toggleId"><input type="hidden" name="to" id="toggleTo"></form>
  <form id="deleteForm" method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="deleteId"></form>

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
    subLabel.textContent = (kat === 'dpo') ? 'Detail Kasus *' : 'Ciri-ciri Fisik *';
  }
}

function submitToggle(id, to) {
  document.getElementById('toggleId').value = id;
  document.getElementById('toggleTo').value = to;
  document.getElementById('toggleForm').submit();
}

function confirmDelete(id) {
  if(confirm('Apakah Anda yakin ingin menghapus data ini? Gambar terkait juga akan dihapus.')) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteForm').submit();
  }
}

toggleFields();
</script>
</body>
</html>