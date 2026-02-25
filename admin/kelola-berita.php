<?php
// admin/kelola-berita.php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// === 1. AUTH GUARD ===
$ALLOWED_ROLES = ['admin','editor'];
require __DIR__ . '/auth_guard.php';

// === 2. DATABASE CONNECTION ===
require __DIR__ . '/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid. Pastikan db_connection.php menghasilkan \$conn.");
}
$conn->set_charset('utf8mb4');

// === 3. KONFIGURASI ===
$KATEGORI_LIST = ['Lalu Lintas', 'Kriminal', 'Humas', 'SDM'];
$DISPLAY_OPTS  = ['Tampilan Berita Utama', 'Berita Terkini', 'Berita Populer'];

// === 4. HELPERS ===
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header("Location: " . $url); exit; }

// Deteksi kolom nama pada tabel editor agar tidak error
function detectEditorLabelField(mysqli $conn): string {
    $fieldsPriority = ['nama_lengkap', 'nama', 'full_name', 'username', 'email'];
    $cols = [];
    $rs = $conn->query("SHOW COLUMNS FROM editor");
    if ($rs) {
        while ($r = $rs->fetch_assoc()) { $cols[] = $r['Field']; }
        $rs->free();
    }
    foreach ($fieldsPriority as $f) {
        if (in_array($f, $cols, true)) return $f;
    }
    return 'id'; // fallback jika tidak ketemu
}

function fetchEditorsMap(mysqli $conn): array {
    $label = detectEditorLabelField($conn);
    $map = [];
    $sql = "SELECT id, `$label` as nama FROM editor";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $map[$r['id']] = $r['nama'];
        }
        $res->free();
    }
    return $map;
}

function handleUpload(?array $file, string $destDir): ?string {
    if (!$file || !isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException("Upload gagal. Kode: " . $file['error']);
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) throw new RuntimeException("Format gambar harus JPG, PNG, atau WEBP.");
    if ((int)$file['size'] > 2 * 1024 * 1024) throw new RuntimeException("Ukuran file terlalu besar (Maks 2MB).");

    if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
    
    $newName = date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $destDir . DIRECTORY_SEPARATOR . $newName)) {
        throw new RuntimeException("Gagal memindahkan file upload.");
    }
    return $newName;
}

// Generate SEO-friendly slug from title
function generateSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    if ($slug === '') $slug = 'berita';
    return $slug;
}

function ensureUniqueSlug(mysqli $conn, string $baseSlug, int $excludeId = 0): string {
    $slug = $baseSlug;
    $counter = 1;
    while (true) {
        $check = $conn->prepare("SELECT id FROM berita WHERE slug = ? AND id != ?");
        $check->bind_param("si", $slug, $excludeId);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows === 0) {
            $check->close();
            break;
        }
        $check->close();
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    return $slug;
}

// === 5. ROUTING & SETUP ===
$mode = $_GET['mode'] ?? 'list';
$id   = (int)($_GET['id'] ?? 0);
$beritaUploadDir = realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR . 'berita';
$beritaUploadUrl = '../uploads/berita/';
$flashError = ''; 
$flashOk = '';

// === 6. PROSES ACTIONS (POST) ===
try {
    // CREATE
    if ($mode === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $judul    = trim($_POST['judul'] ?? '');
        $isi      = $_POST['isi'] ?? ''; // Jangan trim berlebihan agar tag HTML terjaga
        $tanggal  = $_POST['tanggal'] ?? date('Y-m-d');
        $display  = $_POST['display_category'] ?? 'Tampilan Berita Utama';
        
        $kategori = ($display === 'Tampilan Berita Utama') ? trim($_POST['kategori'] ?? '') : '';
        $ed_id    = empty($_POST['editor_id']) ? null : (int)$_POST['editor_id'];
        $gambar   = handleUpload($_FILES['gambar'] ?? null, $beritaUploadDir);

        $slug = ensureUniqueSlug($conn, generateSlug($judul));
        
        $metaDesc = trim($_POST['meta_description'] ?? '');
        if ($metaDesc === '') {
            $metaDesc = mb_substr(strip_tags(preg_replace('/\s+/', ' ', $isi)), 0, 160, 'UTF-8');
        }

        $stmt = $conn->prepare("INSERT INTO berita (judul, slug, isi, meta_description, tanggal, kategori, editor_id, gambar, display_category) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssiss", $judul, $slug, $isi, $metaDesc, $tanggal, $kategori, $ed_id, $gambar, $display);
        
        if (!$stmt->execute()) throw new RuntimeException("Gagal Simpan: " . $stmt->error);
        redirect('kelola-berita.php?ok=created');
    }

    // EDIT
    if ($mode === 'edit' && $id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $judul    = trim($_POST['judul'] ?? '');
        $isi      = $_POST['isi'] ?? ''; // TinyMCE mengirimkan data HTML ke sini
        $tanggal  = $_POST['tanggal'] ?? '';
        $display  = $_POST['display_category'] ?? 'Tampilan Berita Utama';
        
        $kategori = ($display === 'Tampilan Berita Utama') ? trim($_POST['kategori'] ?? '') : '';
        $ed_id    = empty($_POST['editor_id']) ? null : (int)$_POST['editor_id'];

        $slug = ensureUniqueSlug($conn, generateSlug($judul), $id);
        
        $metaDesc = trim($_POST['meta_description'] ?? '');
        if ($metaDesc === '') {
            $metaDesc = mb_substr(strip_tags(preg_replace('/\s+/', ' ', $isi)), 0, 160, 'UTF-8');
        }
        
        $qOld = $conn->query("SELECT gambar FROM berita WHERE id=$id");
        $oldData = $qOld ? $qOld->fetch_assoc() : null;
        
        $newImg = handleUpload($_FILES['gambar'] ?? null, $beritaUploadDir);
        
        $finalImg = $oldData['gambar'] ?? null;
        if (isset($_POST['hapus_gambar'])) $finalImg = null;
        if ($newImg) $finalImg = $newImg;

        $stmt = $conn->prepare("UPDATE berita SET judul=?, slug=?, isi=?, meta_description=?, tanggal=?, kategori=?, editor_id=?, gambar=?, display_category=? WHERE id=?");
        $stmt->bind_param("ssssssissi", $judul, $slug, $isi, $metaDesc, $tanggal, $kategori, $ed_id, $finalImg, $display, $id);
        
        if (!$stmt->execute()) throw new RuntimeException("Gagal Update: " . $stmt->error);
        redirect('kelola-berita.php?ok=updated');
    }

    // DELETE
    if ($mode === 'delete' && $id > 0) {
        $q = $conn->query("SELECT gambar FROM berita WHERE id=$id");
        if ($q && $row = $q->fetch_assoc()) {
            if ($row['gambar']) @unlink($beritaUploadDir . DIRECTORY_SEPARATOR . $row['gambar']);
        }
        $conn->query("DELETE FROM berita WHERE id=$id");
        redirect('kelola-berita.php?ok=deleted');
    }

} catch (Throwable $e) {
    $flashError = $e->getMessage();
}

// === 7. AMBIL DATA UNTUK VIEW ===
if (isset($_GET['ok'])) {
    $msgs = [
        'created' => 'Berita berhasil ditambahkan.',
        'updated' => 'Berita berhasil diperbarui.',
        'deleted' => 'Berita berhasil dihapus.'
    ];
    $flashOk = $msgs[$_GET['ok']] ?? '';
}

$editorMap = fetchEditorsMap($conn);

$beritaList = [];
if ($mode === 'list') {
    $res = $conn->query("SELECT * FROM berita ORDER BY tanggal DESC, id DESC");
    if ($res) {
        while($r = $res->fetch_assoc()) $beritaList[] = $r;
        $res->free();
    } else {
        $flashError = "Gagal mengambil data berita: " . $conn->error;
    }
}

$beritaEdit = null;
if ($mode === 'edit' && $id > 0) {
    $res = $conn->query("SELECT * FROM berita WHERE id=$id");
    $beritaEdit = $res ? $res->fetch_assoc() : null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - Polresta Padang</title>
    <script src="https://cdn.tiny.cloud/1/qxoa1nvib16ymjts7my24crtxmphz7aab621ez64j0gxxfub/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        :root { --primary: #111; --border: #ddd; --bg: #f9f9f9; --danger: #d32f2f; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); color: #333; margin: 0; padding: 20px; }
        .wrap { max-width: 1100px; margin: 0 auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .topbar h1 { margin: 0; font-size: 24px; }
        .btn { padding: 10px 18px; border-radius: 8px; text-decoration: none; cursor: pointer; border: 1px solid var(--border); background: #fff; color: #333; display: inline-flex; align-items: center; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .btn:hover { background: #f0f0f0; }
        .btn-dark { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-dark:hover { background: #333; }
        .btn-danger { color: var(--danger); border-color: #ffcdd2; background: #fff; }
        .btn-danger:hover { background: #ffebee; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th { text-align: left; padding: 12px; background: #f8f9fa; border-bottom: 2px solid #eee; font-size: 13px; text-transform: uppercase; color: #666; }
        .table td { padding: 14px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-err { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-1 { grid-template-columns: 1fr; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #444; }
        input[type="text"], input[type="date"], select, textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: 0.2s; }
        input:focus, select:focus, textarea:focus { border-color: #000; outline: none; }
        textarea { min-height: 300px; resize: vertical; font-family: inherit; }
        .hidden { display: none; }
        .text-muted { color: #888; font-size: 12px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; background: #eee; }
        .badge-main { background: #e3f2fd; color: #1565c0; }
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        .img-preview { width: 120px; height: auto; border-radius: 8px; border: 1px solid #eee; }
    </style>
</head>
<body>

<div class="wrap">
    <div class="topbar">
        <h1>Kelola Berita</h1>
        <div>
            <?php if($mode !== 'list'): ?>
                <a href="kelola-berita.php" class="btn">Kembali</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
                <a href="?mode=create" class="btn btn-dark">+ Tambah Berita</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($flashOk): ?> <div class="alert alert-ok"><?= h($flashOk) ?></div> <?php endif; ?>
    <?php if($flashError): ?> <div class="alert alert-err"><strong>Error:</strong> <?= h($flashError) ?></div> <?php endif; ?>

    <?php if($mode === 'list'): ?>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50">Img</th>
                        <th>Judul Berita</th>
                        <th>Slug</th>
                        <th>Posisi & Kategori</th>
                        <th>Editor</th>
                        <th>Tanggal</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($beritaList)): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 30px; color:#999;">Belum ada berita yang ditambahkan.</td></tr>
                    <?php endif; ?>

                    <?php foreach($beritaList as $b): ?>
                    <tr>
                        <td>
                            <?php if(!empty($b['gambar'])): ?>
                                <img src="<?= h($beritaUploadUrl . $b['gambar']) ?>" class="img-thumb">
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= h($b['judul']) ?></strong></td>
                        <td><small class="text-muted"><?= h($b['slug'] ?? '-') ?></small></td>
                        <td>
                            <span class="badge badge-main"><?= h($b['display_category'] ?? '-') ?></span><br>
                            <?php if(!empty($b['kategori'])): ?>
                                <small class="text-muted">Kat: <?= h($b['kategori']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= h($editorMap[$b['editor_id']] ?? '—') ?></td>
                        <td><?= date('d/m/Y', strtotime($b['tanggal'])) ?></td>
                        <td>
                            <a href="?mode=edit&id=<?= $b['id'] ?>" class="btn" style="padding: 6px 12px; font-size:12px;">Edit</a>
                            <a href="?mode=delete&id=<?= $b['id'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size:12px;" onclick="return confirm('Yakin hapus berita ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if($mode === 'create' || ($mode === 'edit' && $beritaEdit)): ?>
        <div class="card">
            <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <?= $mode === 'create' ? 'Tambah Berita Baru' : 'Edit Berita' ?>
            </h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-1" style="margin-bottom: 20px;">
                    <div>
                        <label>Judul Berita</label>
                        <input type="text" name="judul" value="<?= h($beritaEdit['judul'] ?? '') ?>" required placeholder="Masukkan judul berita...">
                    </div>

                    <div class="grid">
                        <div>
                            <label>Tampilkan Berita Sebagai</label>
                            <select name="display_category" id="display_category" onchange="toggleKategoriLogic()" required>
                                <?php 
                                $currDisp = $beritaEdit['display_category'] ?? 'Tampilan Berita Utama';
                                foreach($DISPLAY_OPTS as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $currDisp === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="kat_box">
                            <label>Kategori Berita</label>
                            <select name="kategori">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach($KATEGORI_LIST as $kat): ?>
                                    <option value="<?= $kat ?>" <?= ($beritaEdit['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Isi Berita</label>
                        <textarea id="isi_editor" name="isi" placeholder="Tulis isi berita lengkap..."><?= h($beritaEdit['isi'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label>Meta Description (SEO) <small class="text-muted">— Opsional, maks 160 karakter. Jika kosong akan otomatis diambil dari isi berita.</small></label>
                        <input type="text" name="meta_description" value="<?= h($beritaEdit['meta_description'] ?? '') ?>" placeholder="Deskripsi singkat untuk mesin pencari..." maxlength="160">
                    </div>

                    <div class="grid">
                        <div>
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" value="<?= $beritaEdit['tanggal'] ?? date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label>Editor (Penulis)</label>
                            <select name="editor_id">
                                <option value="">-- Pilih Editor (Opsional) --</option>
                                <?php foreach($editorMap as $eId => $eName): ?>
                                    <option value="<?= $eId ?>" <?= ($beritaEdit['editor_id'] ?? '') == $eId ? 'selected' : '' ?>>
                                        <?= h($eName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label>Gambar</label>
                        <?php if(!empty($beritaEdit['gambar'])): ?>
                            <div style="background: #f8f8f8; padding: 10px; border-radius: 8px; display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                <img src="<?= $beritaUploadUrl . $beritaEdit['gambar'] ?>" class="img-preview">
                                <div>
                                    <div class="text-muted" style="margin-bottom:5px;">Gambar saat ini</div>
                                    <label style="margin:0; font-weight:normal; cursor:pointer; font-size:14px;">
                                        <input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar ini?
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp">
                        <div class="text-muted" style="margin-top:5px;">Format: JPG, PNG, WEBP. Maksimal 2MB.</div>
                    </div>
                </div>

                <div style="display:flex; gap:12px; margin-top:30px;">
                    <button type="submit" class="btn btn-dark" style="flex:1; padding: 12px 24px; font-weight:bold;">
                        <?= $mode === 'create' ? 'SIMPAN BERITA' : 'UPDATE PERUBAHAN' ?>
                    </button>
                    <a href="kelola-berita.php" class="btn" style="padding: 12px 24px;">Batal</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * INISIALISASI TINYMCE
 */
tinymce.init({
    selector: '#isi_editor',
    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
    toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
    height: 500,
    branding: false,
    promotion: false
});

/**
 * JAVASCRIPT LOGIC
 * Menyembunyikan dropdown kategori jika yang dipilih BUKAN 'Tampilan Berita Utama'
 */
function toggleKategoriLogic() {
    const displaySelect = document.getElementById('display_category');
    const katBox = document.getElementById('kat_box');
    
    if (!displaySelect || !katBox) return;

    if (displaySelect.value === 'Tampilan Berita Utama') {
        katBox.style.display = 'block';
    } else {
        katBox.style.display = 'none';
        const katSelect = katBox.querySelector('select');
        if(katSelect) katSelect.value = ''; 
    }
}

document.addEventListener('DOMContentLoaded', toggleKategoriLogic);
</script>

</body>
</html>