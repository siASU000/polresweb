<?php
// admin/kelola-berita.php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// === AUTH GUARD (sesuaikan bila kamu pakai role) ===
$ALLOWED_ROLES = ['admin','editor']; // boleh diubah
require __DIR__ . '/auth_guard.php';

// === DB ===
require __DIR__ . '/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan db_connection.php menghasilkan \$conn (mysqli).");
}
$conn->set_charset('utf8mb4');

// === DAFTAR KATEGORI BERITA ===
$KATEGORI_LIST = [
  'Lalu Lintas',
  'Kriminal',
  'Humas',
  'SDM'
];

// === HELPERS ===
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $url): void {
  header("Location: " . $url);
  exit;
}

function ensureUploadDir(string $dir): void {
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }
}

function detectEditorLabelField(mysqli $conn): string {
  // Pilih field label yang paling mungkin ada pada tabel editor
  $fieldsPriority = ['nama_lengkap', 'nama', 'full_name', 'username', 'email'];
  $cols = [];
  $rs = $conn->query("SHOW COLUMNS FROM editor");
  if ($rs) {
    while ($r = $rs->fetch_assoc()) {
      $cols[] = $r['Field'];
    }
    $rs->free();
  }
  foreach ($fieldsPriority as $f) {
    if (in_array($f, $cols, true)) return $f;
  }
  return 'id'; // fallback
}

function fetchEditors(mysqli $conn): array {
  $labelField = detectEditorLabelField($conn);
  $editors = [];
  $sql = "SELECT id, `$labelField` AS label FROM editor ORDER BY `$labelField` ASC";
  $rs = $conn->query($sql);
  if ($rs) {
    while ($r = $rs->fetch_assoc()) {
      $editors[] = $r;
    }
    $rs->free();
  }
  return $editors;
}

function validateDate(string $d): bool {
  // format input type="date" => YYYY-MM-DD
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt && $dt->format('Y-m-d') === $d;
}

function handleUpload(?array $file, string $destDir): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }
  if ($file['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException("Upload gagal. Kode error: " . $file['error']);
  }

  $tmp = $file['tmp_name'];
  $origName = (string)($file['name'] ?? '');
  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

  $allowed = ['jpg','jpeg','png','webp'];
  if (!in_array($ext, $allowed, true)) {
    throw new RuntimeException("Format gambar tidak didukung. Gunakan: JPG, PNG, WEBP.");
  }

  // batas 2MB (ubah jika perlu)
  $maxBytes = 2 * 1024 * 1024;
  if ((int)$file['size'] > $maxBytes) {
    throw new RuntimeException("Ukuran gambar terlalu besar. Maksimal 2MB.");
  }

  ensureUploadDir($destDir);

  $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
  $newName = $safeBase . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;

  $destPath = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $newName;
  if (!move_uploaded_file($tmp, $destPath)) {
    throw new RuntimeException("Gagal memindahkan file upload.");
  }

  return $newName; // yang disimpan ke DB
}

// === ROUTING ===
$mode = $_GET['mode'] ?? 'list';   // list | create | edit | delete
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Upload directory (fisik) dan URL base (untuk img src)
$uploadDir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$beritaUploadDir = $uploadDir . DIRECTORY_SEPARATOR . 'berita';
$beritaUploadUrl = '../uploads/berita/';

// === ACTIONS (POST) ===
$flashError = '';
$flashOk = '';

try {
  // CREATE
  if ($mode === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim((string)($_POST['judul'] ?? ''));
    $isi   = trim((string)($_POST['isi'] ?? ''));
    $tanggal = (string)($_POST['tanggal'] ?? '');
    $kategori = trim((string)($_POST['kategori'] ?? ''));
    $editor_id_raw = $_POST['editor_id'] ?? '';
    $editor_id = ($editor_id_raw === '' || $editor_id_raw === null) ? null : (int)$editor_id_raw;

    if ($judul === '') throw new RuntimeException("Judul wajib diisi.");
    if ($isi === '') throw new RuntimeException("Isi berita wajib diisi.");
    if (!validateDate($tanggal)) throw new RuntimeException("Tanggal tidak valid.");
    if ($kategori === '') throw new RuntimeException("Kategori wajib diisi.");

    $gambarName = handleUpload($_FILES['gambar'] ?? null, $beritaUploadDir);

    // Pastikan kolom gambar ada. Jika belum, kamu harus ALTER TABLE.
    $stmt = $conn->prepare("
      INSERT INTO berita (judul, isi, tanggal, kategori, editor_id, gambar)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new RuntimeException("Prepare gagal: " . $conn->error);

    // i bisa null, tapi bind_param butuh tipe tetap -> kita pakai trick:
    // jika editor_id null, set ke null dan gunakan bind_param dengan "i" tetap,
    // tapi harus pakai mysqli_stmt::bind_param tidak mendukung null langsung untuk i?
    // Solusi: gunakan "s" untuk editor_id lalu cast? Lebih aman: set editor_id sebagai NULL via query dynamic:
    // Namun biar rapi, kita bind dan set param via $stmt->send_long_data tidak perlu.
    // Cara aman: gunakan query dengan placeholder dan set editor_id sebagai int atau null via $stmt->bind_param lalu $editor_id = $editor_id ?? null works, mysqli akan kirim NULL jika variabel null.
    $stmt->bind_param("ssssis", $judul, $isi, $tanggal, $kategori, $editor_id, $gambarName);

    if (!$stmt->execute()) {
      throw new RuntimeException("Gagal simpan berita: " . $stmt->error);
    }
    $stmt->close();

    redirect('kelola-berita.php?ok=created');
  }

  // EDIT
  if ($mode === 'edit' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim((string)($_POST['judul'] ?? ''));
    $isi   = trim((string)($_POST['isi'] ?? ''));
    $tanggal = (string)($_POST['tanggal'] ?? '');
    $kategori = trim((string)($_POST['kategori'] ?? ''));
    $editor_id_raw = $_POST['editor_id'] ?? '';
    $editor_id = ($editor_id_raw === '' || $editor_id_raw === null) ? null : (int)$editor_id_raw;

    $hapusGambar = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] === '1';

    if ($judul === '') throw new RuntimeException("Judul wajib diisi.");
    if ($isi === '') throw new RuntimeException("Isi berita wajib diisi.");
    if (!validateDate($tanggal)) throw new RuntimeException("Tanggal tidak valid.");
    if ($kategori === '') throw new RuntimeException("Kategori wajib dipilih.");


    // Ambil data lama (untuk hapus file jika perlu)
    $old = null;
    $stmtOld = $conn->prepare("SELECT gambar, kategori FROM berita WHERE id=? LIMIT 1");
    $stmtOld->bind_param("i", $id);
    $stmtOld->execute();
    $resOld = $stmtOld->get_result();
    $old = $resOld ? $resOld->fetch_assoc() : null;
    $stmtOld->close();

    if (!$old) throw new RuntimeException("Data berita tidak ditemukan.");

    $newGambar = null;
    if (isset($_FILES['gambar']) && ($_FILES['gambar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $newGambar = handleUpload($_FILES['gambar'], $beritaUploadDir);
    }

    $finalGambar = $old['gambar'] ?? null;

    if ($hapusGambar) {
      $finalGambar = null;
    }
    if ($newGambar !== null) {
      $finalGambar = $newGambar;
    }

    $stmt = $conn->prepare("
      UPDATE berita
SET judul=?, isi=?, tanggal=?, kategori=?, editor_id=?, gambar=?
WHERE id=?

    ");
    if (!$stmt) throw new RuntimeException("Prepare gagal: " . $conn->error);

    $stmt->bind_param(
  "ssssisi",
  $judul,
  $isi,
  $tanggal,
  $kategori,
  $editor_id,
  $finalGambar,
  $id
);


    if (!$stmt->execute()) {
      throw new RuntimeException("Gagal update berita: " . $stmt->error);
    }
    $stmt->close();

    // Hapus file lama bila diganti / dihapus
    $oldName = $old['gambar'] ?? '';
    if ($oldName) {
      $oldPath = $beritaUploadDir . DIRECTORY_SEPARATOR . $oldName;
      $needDelete = false;

      if ($hapusGambar) $needDelete = true;
      if ($newGambar !== null && $newGambar !== $oldName) $needDelete = true;

      if ($needDelete && is_file($oldPath)) {
        @unlink($oldPath);
      }
    }

    redirect('kelola-berita.php?ok=updated');
  }

  // DELETE
  if ($mode === 'delete' && $id > 0) {
    // ambil gambar dulu untuk dihapus filenya
    $stmtOld = $conn->prepare("SELECT gambar FROM berita WHERE id=? LIMIT 1");
    $stmtOld->bind_param("i", $id);
    $stmtOld->execute();
    $resOld = $stmtOld->get_result();
    $old = $resOld ? $resOld->fetch_assoc() : null;
    $stmtOld->close();

    if (!$old) throw new RuntimeException("Data berita tidak ditemukan.");

    $stmt = $conn->prepare("DELETE FROM berita WHERE id=?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
      throw new RuntimeException("Gagal hapus berita: " . $stmt->error);
    }
    $stmt->close();

    // hapus file gambar jika ada
    $oldName = $old['gambar'] ?? '';
    if ($oldName) {
      $oldPath = $beritaUploadDir . DIRECTORY_SEPARATOR . $oldName;
      if (is_file($oldPath)) @unlink($oldPath);
    }

    redirect('kelola-berita.php?ok=deleted');
  }

} catch (Throwable $e) {
  $flashError = $e->getMessage();
}

// Flash via query
if (isset($_GET['ok'])) {
  $map = [
    'created' => 'Berita berhasil ditambahkan.',
    'updated' => 'Berita berhasil diperbarui.',
    'deleted' => 'Berita berhasil dihapus.',
  ];
  $flashOk = $map[$_GET['ok']] ?? '';
}

// === DATA FOR VIEWS ===
$editors = fetchEditors($conn);

// list data
$beritaList = [];
if ($mode === 'list') {
  $sql = "
    SELECT b.id, b.judul, b.kategori, b.tanggal, b.gambar, b.editor_id
    FROM berita b
    ORDER BY b.tanggal DESC, b.id DESC
  ";
  $rs = $conn->query($sql);
  if ($rs) {
    while ($r = $rs->fetch_assoc()) $beritaList[] = $r;
    $rs->free();
  }
}

// edit data
$beritaEdit = null;
if ($mode === 'edit' && $id > 0) {
  $stmt = $conn->prepare("SELECT * FROM berita WHERE id=? LIMIT 1");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $beritaEdit = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!$beritaEdit) {
    $flashError = $flashError ?: "Data berita tidak ditemukan.";
    $mode = 'list';
  }
}

// default tanggal untuk create
$today = date('Y-m-d');

// === UI FLAGS (INI YANG MEMPERBAIKI MASALAH KAMU) ===
$isCreate = ($mode === 'create');
$isEdit   = ($mode === 'edit');
$isList   = ($mode === 'list');

// Tombol kembali harus selalu balik ke LIST (halaman 1)
$backToListUrl = './dashboard.php';

// Tombol tambah hanya muncul di LIST saja
$showTambahBtn = $isList;

// Title
$pageTitle = 'Kelola Berita';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="admin.css" />
  <style>
    /* Minimal styling tambahan biar mirip tampilan kamu */
    .wrap { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
    .topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid #ddd; background:#fff; cursor:pointer; text-decoration:none; color:#111; }
    .btn-dark { background:#111; color:#fff; border-color:#111; }
    .card { background:#fff; border:1px solid #e9e9e9; border-radius:14px; padding:18px; margin-top:14px; }
    .table { width:100%; border-collapse:collapse; }
    .table th, .table td { padding:10px 8px; border-bottom:1px solid #eee; text-align:left; vertical-align:top; }
    .muted { color:#666; font-size: 13px; }
    .alert { padding:12px 14px; border-radius:10px; margin-top:14px; }
    .alert-ok { background:#eefbf0; border:1px solid #bfe9c8; }
    .alert-err { background:#fff1f1; border:1px solid #f1b7b7; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:14px; }
    .grid-1 { grid-template-columns: 1fr; }
    label { display:block; font-weight:600; margin-bottom:6px; }
    input[type="text"], input[type="date"], textarea, select {
      width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; outline:none;
    }
    textarea { min-height: 180px; resize: vertical; }
    .actions { display:flex; gap:8px; }
    .img-thumb { width:70px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #eee; }
    .btn-danger { border-color:#ffb3b3; color:#b00000; background:#fff; }
    .btn-danger:hover { background:#fff3f3; }
    .btn-link { border:none; background:none; padding:0; color:#0b57d0; cursor:pointer; }
    .submitbar { margin-top:14px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .btn-wide { width:100%; justify-content:center; padding:14px 16px; border-radius:12px; }
    @media (max-width: 900px) {
      .grid { grid-template-columns: 1fr; }
      .topbar { align-items:flex-start; flex-direction:column; }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="topbar">
      <h1 style="margin:0;"><?= h($pageTitle) ?></h1>

      <div class="actions">
        <!-- KEMBALI: selalu ke LIST (halaman 1) -->
        <a class="btn" href="<?= h($backToListUrl) ?>">Kembali</a>

        <!-- TAMBAH: hanya muncul di HALAMAN 1 (LIST) -->
        <?php if ($showTambahBtn): ?>
          <a class="btn btn-dark" href="kelola-berita.php?mode=create">+ Tambah Berita</a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($flashOk): ?>
      <div class="alert alert-ok"><?= h($flashOk) ?></div>
    <?php endif; ?>

    <?php if ($flashError): ?>
      <div class="alert alert-err"><strong>Error:</strong> <?= h($flashError) ?></div>
    <?php endif; ?>

    <!-- ===================== HALAMAN 1: LIST ===================== -->
    <?php if ($isList): ?>
      <div class="card">
        <h2 style="margin-top:0;">Daftar Berita</h2>

        <table class="table">
          <thead>
            <tr>
              <th style="width:70px;">ID</th>
              <th>Judul</th>
              <th style="width:140px;">Tanggal</th>
              <th style="width:180px;">Editor</th>
              <th style="width:110px;">Gambar</th>
              <th style="width:180px;">Aksi</th>
              <th>Kategori</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($beritaList) === 0): ?>
              <tr>
                <td colspan="6" class="muted">Belum ada data berita.</td>
              </tr>
            <?php else: ?>
              <?php
                // map editor id -> label
                $editorMap = [];
                foreach ($editors as $ed) $editorMap[(int)$ed['id']] = (string)$ed['label'];
              ?>
              <?php foreach ($beritaList as $b): ?>
                <tr>
                  <td><?= (int)$b['id'] ?></td>
                  <td><?= h((string)$b['judul']) ?></td>
                  <td><?= h((string)$b['tanggal']) ?></td>
                  <td>
                    <?php
                      $eid = $b['editor_id'];
                      if ($eid === null) echo '<span class="muted">—</span>';
                      else echo h($editorMap[(int)$eid] ?? ('ID ' . (int)$eid));
                    ?>
                  </td>
                  <td>
                    <?php if (!empty($b['gambar'])): ?>
                      <img class="img-thumb" src="<?= h($beritaUploadUrl . $b['gambar']) ?>" alt="Gambar">
                    <?php else: ?>
                      <span class="muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="actions">
                      <a class="btn" href="kelola-berita.php?mode=edit&id=<?= (int)$b['id'] ?>">Edit</a>
                      <a class="btn btn-danger"
                         href="kelola-berita.php?mode=delete&id=<?= (int)$b['id'] ?>"
                         onclick="return confirm('Yakin hapus berita ini?');">
                        Hapus
                        <td><?= h((string)($b['kategori'] ?? '—')) ?></td>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- ===================== HALAMAN 2: CREATE ===================== -->
    <?php if ($isCreate): ?>
      <div class="card">
        <h2 style="margin-top:0;">Tambah Berita</h2>

        <form method="POST" enctype="multipart/form-data" class="grid grid-1">
          <div>
            <label>Judul</label>
            <input type="text" name="judul" value="<?= h((string)($_POST['judul'] ?? '')) ?>" required>
          </div>

          <div>
  <label>Kategori Berita</label>
  <select name="kategori" required>
    <option value="">-- Pilih Kategori --</option>
    <?php foreach ($KATEGORI_LIST as $kat): ?>
      <option value="<?= h($kat) ?>"
        <?= (($_POST['kategori'] ?? '') === $kat) ? 'selected' : '' ?>>
        <?= h($kat) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

          <div>
            <label>Isi Berita</label>
            <textarea name="isi" required><?= h((string)($_POST['isi'] ?? '')) ?></textarea>
          </div>

          <div class="grid">
            <div>
              <label>Tanggal</label>
              <input type="date" name="tanggal" value="<?= h((string)($_POST['tanggal'] ?? $today)) ?>" required>
            </div>

            <div>
              <label>Editor</label>
              <select name="editor_id">
                <option value="">-- Pilih editor (opsional) --</option>
                <?php
                  $selected = (string)($_POST['editor_id'] ?? '');
                ?>
                <?php foreach ($editors as $ed): ?>
                  <option value="<?= (int)$ed['id'] ?>" <?= ($selected !== '' && (int)$selected === (int)$ed['id']) ? 'selected' : '' ?>>
                    <?= h((string)$ed['label']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="muted" style="margin-top:6px;">Jika tidak dipilih, editor_id akan disimpan sebagai NULL.</div>
            </div>
          </div>

          <div>
            <label>Gambar (opsional)</label>
            <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
            <div class="muted" style="margin-top:6px;">
              Folder upload: uploads/berita (dibuat otomatis jika belum ada). Maks 2MB. Format: JPG/PNG/WEBP.
            </div>
          </div>

          <div class="submitbar">
            <a class="btn" href="<?= h($backToListUrl) ?>">Batal</a>
            <button class="btn btn-dark btn-wide" type="submit">Simpan</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <!-- ===================== HALAMAN EDIT ===================== -->
    <?php if ($isEdit && $beritaEdit): ?>
      <div class="card">
        <h2 style="margin-top:0;">Edit Berita</h2>

        <form method="POST" enctype="multipart/form-data" class="grid grid-1">
          <div>
            <label>Judul</label>
            <input type="text" name="judul" value="<?= h((string)($beritaEdit['judul'] ?? '')) ?>" required>
          </div>

          <div>
            <label>Isi Berita</label>
            <textarea name="isi" required><?= h((string)($beritaEdit['isi'] ?? '')) ?></textarea>
          </div>

          <div class="grid">
            <div>
              <label>Tanggal</label>
              <input type="date" name="tanggal" value="<?= h((string)($beritaEdit['tanggal'] ?? $today)) ?>" required>
            </div>

            <div>
              <label>Editor</label>
              <select name="editor_id">
                <option value="">-- Pilih editor (opsional) --</option>
                <?php
                  $selectedE = (string)($beritaEdit['editor_id'] ?? '');
                ?>
                <?php foreach ($editors as $ed): ?>
                  <option value="<?= (int)$ed['id'] ?>" <?= ($selectedE !== '' && (int)$selectedE === (int)$ed['id']) ? 'selected' : '' ?>>
                    <?= h((string)$ed['label']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="muted" style="margin-top:6px;">Kosongkan untuk NULL.</div>
            </div>
          </div>

          <div>
            <label>Gambar</label>
            <?php if (!empty($beritaEdit['gambar'])): ?>
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <img class="img-thumb" src="<?= h($beritaUploadUrl . $beritaEdit['gambar']) ?>" alt="Gambar">
                <label style="margin:0;font-weight:500;">
                  <input type="checkbox" name="hapus_gambar" value="1">
                  Hapus gambar sekarang
                </label>
              </div>
            <?php else: ?>
              <div class="muted" style="margin-bottom:10px;">Belum ada gambar.</div>
            <?php endif; ?>

            <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
            <div class="muted" style="margin-top:6px;">Jika upload gambar baru, gambar lama akan diganti.</div>
          </div>

          <div class="submitbar">
            <a class="btn" href="<?= h($backToListUrl) ?>">Batal</a>
            <button class="btn btn-dark btn-wide" type="submit">Update</button>
          </div>
        </form>
      </div>
    <?php endif; ?>

  </div>
</body>
</html>
