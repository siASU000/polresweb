<?php
declare(strict_types=1);

$ALLOWED_ROLES = ['admin','editor']; // atau ['admin'] saja
require __DIR__ . '/auth_guard.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan admin/db_connection.php menghasilkan \$conn (mysqli).");
}
$conn->set_charset('utf8mb4');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$uploadDir = __DIR__ . '/../uploads/galeri/';
$uploadBase = 'uploads/galeri/'; // untuk dipakai di <img src="">
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}

$flash = ['ok' => '', 'err' => ''];

/** Ambil data galeri */
function fetchGaleri(mysqli $conn): array {
  $data = [];
  $sql = "SELECT id, judul, deskripsi, gambar, alt_text, is_active, urutan, created_at
          FROM galeri
          ORDER BY urutan ASC, created_at DESC, id DESC";
  $rs = $conn->query($sql);
  if ($rs) {
    while ($r = $rs->fetch_assoc()) $data[] = $r;
    $rs->free();
  }
  return $data;
}

/** Validasi ekstensi & mimetype gambar */
function validateImageUpload(array $file, string &$err): bool {
  if (!isset($file['error']) || is_array($file['error'])) {
    $err = "Upload tidak valid.";
    return false;
  }
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $err = "Upload gagal. Kode error: " . (string)$file['error'];
    return false;
  }
  if (($file['size'] ?? 0) > 4 * 1024 * 1024) { // 4MB
    $err = "Ukuran file terlalu besar. Maks 4MB.";
    return false;
  }

  $tmp = $file['tmp_name'] ?? '';
  if (!$tmp || !is_uploaded_file($tmp)) {
    $err = "File sementara tidak ditemukan.";
    return false;
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) {
    $err = "Format tidak didukung. Gunakan JPG/PNG/WEBP.";
    return false;
  }
  return true;
}

/** Generate nama file aman */
function makeSafeFilename(string $ext): string {
  $rand = bin2hex(random_bytes(8));
  return 'galeri_' . date('Ymd_His') . '_' . $rand . '.' . $ext;
}

/** Hapus file fisik jika ada */
function deletePhysicalFile(string $absPath): void {
  if ($absPath && file_exists($absPath) && is_file($absPath)) {
    @unlink($absPath);
  }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ==========================
    HANDLE ACTIONS
========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($action === 'create') {
    $judul = trim((string)($_POST['judul'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $alt_text = trim((string)($_POST['alt_text'] ?? ''));
    $urutan = (int)($_POST['urutan'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($judul === '') {
      $flash['err'] = "Judul wajib diisi.";
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === UPLOAD_ERR_NO_FILE) {
      $flash['err'] = "File gambar wajib diupload.";
    } else {
      $err = '';
      if (!validateImageUpload($_FILES['gambar'], $err)) {
        $flash['err'] = $err;
      } else {
        $tmp = $_FILES['gambar']['tmp_name'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];
        $filename = makeSafeFilename($ext);
        $dest = $uploadDir . $filename;

        if (!@move_uploaded_file($tmp, $dest)) {
          $flash['err'] = "Gagal menyimpan file ke server.";
        } else {
          $stmt = $conn->prepare("INSERT INTO galeri (judul, deskripsi, gambar, alt_text, is_active, urutan) VALUES (?,?,?,?,?,?)");
          if (!$stmt) {
            deletePhysicalFile($dest);
            $flash['err'] = "Prepare query gagal: " . $conn->error;
          } else {
            $stmt->bind_param("ssssii", $judul, $deskripsi, $filename, $alt_text, $is_active, $urutan);
            if (!$stmt->execute()) {
              deletePhysicalFile($dest);
              $flash['err'] = "Simpan data gagal: " . $stmt->error;
            } else {
              $flash['ok'] = "Galeri berhasil ditambahkan.";
            }
            $stmt->close();
          }
        }
      }
    }
  }

  if ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $to = (int)($_POST['to'] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare("UPDATE galeri SET is_active=? WHERE id=?");
      if ($stmt) {
        $stmt->bind_param("ii", $to, $id);
        $stmt->execute();
        $stmt->close();
        $flash['ok'] = "Status galeri berhasil diubah.";
      }
    }
  }

  if ($action === 'order') {
    $id = (int)($_POST['id'] ?? 0);
    $urutan = (int)($_POST['urutan'] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare("UPDATE galeri SET urutan=? WHERE id=?");
      if ($stmt) {
        $stmt->bind_param("ii", $urutan, $id);
        $stmt->execute();
        $stmt->close();
        $flash['ok'] = "Urutan berhasil diperbarui.";
      }
    }
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare("SELECT gambar FROM galeri WHERE id=? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        $gambar = $row['gambar'] ?? '';
        $abs = $uploadDir . $gambar;

        $del = $conn->prepare("DELETE FROM galeri WHERE id=?");
        if ($del) {
          $del->bind_param("i", $id);
          if ($del->execute()) {
            deletePhysicalFile($abs);
            $flash['ok'] = "Galeri berhasil dihapus.";
          }
          $del->close();
        }
      }
    }
  }
}

$items = fetchGaleri($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola Galeri - Admin Polresta Padang</title>
  <link rel="stylesheet" href="admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #1f2c7d;
      --accent: #f1c40f;
      --bg: #f4f7f6;
      --text: #333;
      --success: #0d6a2b;
      --danger: #b71c1c;
    }

    body { font-family: 'Poppins', sans-serif; background: var(--bg); color: var(--text); }
    
    .admin-shell { max-width: 1400px; margin: 0 auto; padding: 20px; }
    
    /* Header Stying */
    .header-panel { 
      background: white; 
      padding: 25px; 
      border-radius: 15px; 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      box-shadow: 0 4px 6px rgba(0,0,0,0.02);
      margin-bottom: 30px;
    }
    .header-panel h1 { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }

    /* Alerts */
    .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; font-weight: 500; }
    .alert.ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.err { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .main-grid { display: grid; grid-template-columns: 380px 1fr; gap: 30px; }
    @media (max-width: 1100px) { .main-grid { grid-template-columns: 1fr; } }

    /* Cards */
    .card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.03); height: fit-content; }
    .card h3 { margin: 0 0 20px 0; font-size: 18px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }

    /* Forms */
    .field { margin-bottom: 20px; }
    .field label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #555; }
    .field input[type="text"], .field input[type="number"], .field textarea, .field input[type="file"] {
      width: 100%; padding: 12px; border: 1.5px solid #eee; border-radius: 10px; font-family: inherit; transition: 0.3s;
    }
    .field input:focus, .field textarea:focus { border-color: var(--primary); outline: none; background: #fcfdff; }
    
    .btn { 
      display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; 
      border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 14px; 
      transition: 0.3s; border: none; text-decoration: none;
    }
    .btn.primary { background: var(--primary); color: white; width: 100%; justify-content: center; }
    .btn.primary:hover { opacity: 0.9; transform: translateY(-2px); }
    .btn.outline { background: white; border: 1.5px solid #ddd; color: #555; }
    .btn.outline:hover { background: #f9f9f9; }
    .btn.danger { background: #fff5f5; color: var(--danger); }
    .btn.danger:hover { background: var(--danger); color: white; }
    .btn.small { padding: 6px 12px; font-size: 12px; }

    /* Table Styling */
    .table-container { overflow-x: auto; }
    .table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .table th { padding: 15px; font-size: 12px; text-transform: uppercase; color: #888; text-align: left; }
    .table td { padding: 15px; background: #fff; vertical-align: middle; border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9; }
    .table td:first-child { border-left: 1px solid #f9f9f9; border-radius: 12px 0 0 12px; }
    .table td:last-child { border-right: 1px solid #f9f9f9; border-radius: 0 12px 12px 0; }
    .table tr:hover td { background: #fcfcfc; }

    .thumb-wrapper { position: relative; width: 100px; height: 70px; border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
    .thumb { width: 100%; height: 100%; object-fit: cover; }
    
    .badge { padding: 5px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; display: inline-block; }
    .badge.on { background: #e8f5e9; color: var(--success); }
    .badge.off { background: #fff3e0; color: #e65100; }

    .action-group { display: flex; gap: 10px; }
    .order-input { width: 60px; padding: 5px; border-radius: 6px; border: 1px solid #ddd; text-align: center; }
    .muted { color: #888; font-size: 12px; }
  </style>
</head>

<body>
  <div class="admin-shell">
    <header class="header-panel">
      <div>
        <h1><i class="fas fa-images"></i> Manajemen Galeri</h1>
        <p class="muted">Atur publikasi foto kegiatan Polresta Padang</p>
      </div>
      <div class="action-group">
        <a class="btn outline" href="dashboard.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <a class="btn outline" href="../galeri.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Web</a>
      </div>
    </header>

    <?php if ($flash['ok']): ?>
      <div class="alert ok"><i class="fas fa-check-circle"></i> <?= h($flash['ok']) ?></div>
    <?php endif; ?>
    <?php if ($flash['err']): ?>
      <div class="alert err"><i class="fas fa-exclamation-triangle"></i> <?= h($flash['err']) ?></div>
    <?php endif; ?>

    <div class="main-grid">
      <aside>
        <div class="card">
          <h3><i class="fas fa-plus-circle"></i> Tambah Foto</h3>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">

            <div class="field">
              <label>Judul Dokumentasi *</label>
              <input type="text" name="judul" required placeholder="Contoh: Operasi Lilin Singgalang">
            </div>

            <div class="field">
              <label>Keterangan Singkat</label>
              <textarea name="deskripsi" placeholder="Jelaskan sedikit tentang foto ini..."></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 80px; gap:10px;">
              <div class="field">
                <label>Alt Text (SEO)</label>
                <input type="text" name="alt_text" placeholder="Deskripsi untuk tuna netra">
              </div>
              <div class="field">
                <label>Urutan</label>
                <input type="number" name="urutan" value="0">
              </div>
            </div>

            <div class="field">
              <label>File Gambar (Rekomendasi 4:3)</label>
              <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" required>
              <small class="muted">Maksimal file 4MB (JPG, PNG, WEBP)</small>
            </div>

            <div class="field" style="display:flex; align-items:center; gap:10px; background:#f9f9f9; padding:10px; border-radius:10px;">
              <input type="checkbox" id="is_active" name="is_active" checked style="width:18px; height:18px;">
              <label for="is_active" style="margin:0;">Langsung Terbitkan</label>
            </div>

            <button class="btn primary" type="submit"><i class="fas fa-save"></i> Upload Galeri</button>
          </form>
        </div>
      </aside>

      <section>
        <div class="card">
          <h3><i class="fas fa-list"></i> Koleksi Galeri</h3>
          <div class="table-container">
            <table class="table">
              <thead>
                <tr>
                  <th>Visual</th>
                  <th>Detail Informasi</th>
                  <th>Status</th>
                  <th>Urutan</th>
                  <th style="text-align:right;">Opsi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($items) === 0): ?>
                  <tr><td colspan="5" style="text-align:center;" class="muted">Belum ada koleksi foto.</td></tr>
                <?php else: ?>
                  <?php foreach ($items as $it): 
                    $imgRel = $uploadBase . ($it['gambar'] ?? '');
                    $imgAbs = $uploadDir . ($it['gambar'] ?? '');
                    $hasImg = ($it['gambar'] ?? '') !== '' && file_exists($imgAbs);
                    $judul = (string)($it['judul'] ?? '');
                    $active = (int)($it['is_active'] ?? 0);
                  ?>
                  <tr>
                    <td>
                      <div class="thumb-wrapper">
                        <?php if ($hasImg): ?>
                          <img class="thumb" src="../<?= h($imgRel) ?>" alt="thumb">
                        <?php else: ?>
                          <div style="background:#eee; height:100%; display:flex; align-items:center; justify-content:center; font-size:10px;">N/A</div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <div style="font-weight:600; color:var(--primary);"><?= h($judul) ?></div>
                      <div class="muted" style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        <?= h((string)$it['deskripsi']) ?>
                      </div>
                    </td>
                    <td>
                      <span class="badge <?= $active === 1 ? 'on' : 'off' ?>">
                        <?= $active === 1 ? 'PUBLISHED' : 'DRAFT' ?>
                      </span>
                    </td>
                    <td>
                      <form method="post" style="display:flex; gap:5px;">
                        <input type="hidden" name="action" value="order">
                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <input type="number" name="urutan" value="<?= (int)$it['urutan'] ?>" class="order-input">
                        <button class="btn outline small" type="submit"><i class="fas fa-sync"></i></button>
                      </form>
                    </td>
                    <td>
                      <div class="action-group" style="justify-content: flex-end;">
                        <form method="post">
                          <input type="hidden" name="action" value="toggle">
                          <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                          <input type="hidden" name="to" value="<?= $active === 1 ? 0 : 1 ?>">
                          <button class="btn outline small" type="submit" title="Ubah Status">
                            <i class="fas <?= $active === 1 ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                          </button>
                        </form>

                        <form method="post" onsubmit="return confirm('Hapus permanen foto ini?');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                          <button class="btn danger small" type="submit"><i class="fas fa-trash"></i></button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </div>
</body>
</html>