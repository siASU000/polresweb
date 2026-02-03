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
  // coba buat otomatis
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

  // 1) Tambah galeri (upload)
  if ($action === 'create') {
    $judul = trim((string)($_POST['judul'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $alt_text = trim((string)($_POST['alt_text'] ?? ''));
    $urutan = (int)($_POST['urutan'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($judul === '') {
      $flash['err'] = "Judul wajib diisi.";
    } elseif (!isset($_FILES['gambar'])) {
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
          $flash['err'] = "Gagal menyimpan file ke server. Cek permission folder uploads/galeri.";
        } else {
          $stmt = $conn->prepare("INSERT INTO galeri (judul, deskripsi, gambar, alt_text, is_active, urutan)
                                  VALUES (?,?,?,?,?,?)");
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

  // 2) Toggle aktif/nonaktif
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
      } else {
        $flash['err'] = "Query gagal: " . $conn->error;
      }
    } else {
      $flash['err'] = "ID tidak valid.";
    }
  }

  // 3) Update urutan
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
      } else {
        $flash['err'] = "Query gagal: " . $conn->error;
      }
    } else {
      $flash['err'] = "ID tidak valid.";
    }
  }

  // 4) Hapus galeri (hapus file + row)
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      // ambil nama file dulu
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
            // hapus file fisik setelah row terhapus
            deletePhysicalFile($abs);
            $flash['ok'] = "Galeri berhasil dihapus.";
          } else {
            $flash['err'] = "Hapus data gagal: " . $del->error;
          }
          $del->close();
        } else {
          $flash['err'] = "Query gagal: " . $conn->error;
        }
      } else {
        $flash['err'] = "Query gagal: " . $conn->error;
      }
    } else {
      $flash['err'] = "ID tidak valid.";
    }
  }
}

// data list
$items = fetchGaleri($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola Galeri - Admin Polresta Padang</title>
  <link rel="stylesheet" href="admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    .alert { padding: 12px 14px; border-radius: 10px; margin: 12px 0 18px; font-weight: 600; }
    .alert.ok { background: #e9fff0; border: 1px solid #b6f2c8; color: #0d6a2b; }
    .alert.err { background: #fff0f0; border: 1px solid #ffb8b8; color: #8a0d0d; }

    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    @media (max-width: 900px){ .grid-2 { grid-template-columns: 1fr; } }

    .card { background: #fff; border-radius: 14px; padding: 18px; border: 1px solid #eaeaea; }
    .card h3 { margin-bottom: 10px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 700px){ .form-row { grid-template-columns: 1fr; } }

    .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
    .field label { font-weight: 600; font-size: 13px; color: #222; }
    .field input[type="text"],
    .field input[type="number"],
    .field textarea {
      width: 100%;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 10px 12px;
      outline: none;
    }
    .field textarea { min-height: 90px; resize: vertical; }

    .btn { padding: 10px 14px; border-radius: 10px; border: 1px solid #ccc; cursor: pointer; font-weight: 700; background: #fff; }
    .btn.primary { background: #1f2c7d; border-color: #1f2c7d; color: #fff; }
    .btn.danger { background: #b71c1c; border-color: #b71c1c; color: #fff; }
    .btn.small { padding: 8px 10px; border-radius: 10px; font-weight: 700; font-size: 12px; }

    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { padding: 12px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    .table th { text-align: left; font-size: 12px; letter-spacing: .3px; text-transform: uppercase; color: #444; }
    .thumb { width: 120px; height: 80px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; background: #f5f5f5; display:block; }
    .badge { display:inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; }
    .badge.on { background:#e7fff0; border:1px solid #b6f2c8; color:#0d6a2b; }
    .badge.off { background:#fff1e7; border:1px solid #ffd2b6; color:#7a2a00; }

    .actions { display:flex; flex-wrap:wrap; gap:8px; }
    .inline { display:inline-flex; gap:8px; align-items:center; }
    .muted { color:#666; font-size:12px; }
  </style>
</head>

<body class="admin-dashboard-page">
  <main class="admin-shell">
    <section class="admin-card">
      <div class="admin-header">
        <div>
          <h1 style="margin:0;">Kelola Galeri</h1>
          <p class="muted" style="margin:6px 0 0;">Upload foto galeri, atur urutan, dan aktif/nonaktif tampilan di halaman publik.</p>
        </div>
        <div class="actions">
          <a class="btn" href="dashboard.php">← Kembali</a>
          <a class="btn" href="../galeri.php" target="_blank" rel="noopener">Lihat Galeri Publik</a>
        </div>
      </div>

      <?php if ($flash['ok']): ?>
        <div class="alert ok"><?= h($flash['ok']) ?></div>
      <?php endif; ?>
      <?php if ($flash['err']): ?>
        <div class="alert err"><?= h($flash['err']) ?></div>
      <?php endif; ?>

      <div class="grid-2">
        <!-- FORM UPLOAD -->
        <div class="card">
          <h3>Tambah Foto Galeri</h3>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">

            <div class="field">
              <label>Judul *</label>
              <input type="text" name="judul" required maxlength="160" placeholder="Contoh: Kegiatan Patroli / Apel / Sosialisasi">
            </div>

            <div class="field">
              <label>Deskripsi (opsional)</label>
              <textarea name="deskripsi" placeholder="Tulis deskripsi singkat..."></textarea>
            </div>

            <div class="form-row">
              <div class="field">
                <label>Alt Text (opsional)</label>
                <input type="text" name="alt_text" maxlength="180" placeholder="Contoh: Dokumentasi kegiatan Polresta Padang">
              </div>
              <div class="field">
                <label>Urutan (opsional)</label>
                <input type="number" name="urutan" value="0" min="0" step="1">
              </div>
            </div>

            <div class="field">
              <label>Gambar * (JPG/PNG/WEBP, maks 4MB)</label>
              <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" required>
            </div>

            <div class="field inline">
              <input type="checkbox" id="is_active" name="is_active" checked>
              <label for="is_active" style="margin:0;">Tampilkan (aktif)</label>
            </div>

            <div style="margin-top:12px;">
              <button class="btn primary" type="submit">Simpan</button>
            </div>
          </form>
        </div>

        <!-- LIST -->
        <div class="card">
          <h3>Daftar Galeri</h3>
          <p class="muted" style="margin-top:-6px;">Urutan kecil tampil lebih dulu. Status <b>Aktif</b> saja yang tampil di galeri publik.</p>

          <div style="overflow:auto;">
            <table class="table">
              <thead>
                <tr>
                  <th>Foto</th>
                  <th>Info</th>
                  <th>Status</th>
                  <th>Urutan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php if (count($items) === 0): ?>
                <tr><td colspan="5" class="muted">Belum ada data galeri.</td></tr>
              <?php else: ?>
                <?php foreach ($items as $it): ?>
                  <?php
                    $imgRel = $uploadBase . ($it['gambar'] ?? '');
                    $imgAbs = $uploadDir . ($it['gambar'] ?? '');
                    $hasImg = ($it['gambar'] ?? '') !== '' && file_exists($imgAbs);
                    $judul = (string)($it['judul'] ?? '');
                    $desc  = (string)($it['deskripsi'] ?? '');
                    $alt   = (string)($it['alt_text'] ?? $judul);
                    $active = (int)($it['is_active'] ?? 0);
                  ?>
                  <tr>
                    <td>
                      <?php if ($hasImg): ?>
                        <img class="thumb" src="../<?= h($imgRel) ?>" alt="<?= h($alt) ?>">
                      <?php else: ?>
                        <div class="thumb" style="display:flex;align-items:center;justify-content:center;color:#777;font-weight:700;">No Image</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="font-weight:800;"><?= h($judul) ?></div>
                      <?php if ($desc): ?>
                        <div class="muted"><?= h($desc) ?></div>
                      <?php endif; ?>
                      <div class="muted">File: <?= h((string)($it['gambar'] ?? '-')) ?></div>
                    </td>
                    <td>
                      <?php if ($active === 1): ?>
                        <span class="badge on">AKTIF</span>
                      <?php else: ?>
                        <span class="badge off">NONAKTIF</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="post" class="inline" style="gap:6px;">
                        <input type="hidden" name="action" value="order">
                        <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <input type="number" name="urutan" value="<?= (int)($it['urutan'] ?? 0) ?>" style="width:80px;padding:8px 10px;border-radius:10px;border:1px solid #ddd;">
                        <button class="btn small" type="submit">Update</button>
                      </form>
                    </td>
                    <td>
                      <div class="actions">
                        <form method="post">
                          <input type="hidden" name="action" value="toggle">
                          <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                          <input type="hidden" name="to" value="<?= $active === 1 ? 0 : 1 ?>">
                          <button class="btn small" type="submit"><?= $active === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>

                        <form method="post" onsubmit="return confirm('Hapus item galeri ini? File gambarnya juga akan dihapus.');">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                          <button class="btn danger small" type="submit">Hapus</button>
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
      </div>
    </section>
  </main>
</body>
</html>
