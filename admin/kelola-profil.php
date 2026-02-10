<?php
// admin/kelola-profil.php
declare(strict_types=1);

$ALLOWED_ROLES = ['admin', 'editor'];
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$flash = null;

// 1. AMBIL DATA SAAT INI
$res = $conn->query("SELECT * FROM profil_settings WHERE id=1");
$p = $res ? $res->fetch_assoc() : [];

// 2. PROSES UPDATE SAAT TOMBOL SIMPAN DIKLIK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kapolres = trim($_POST['nama_kapolres'] ?? '');
    $nama_wakapolres = trim($_POST['nama_wakapolres'] ?? '');
    $visi = trim($_POST['visi'] ?? '');
    $misi = trim($_POST['misi'] ?? '');
    $sejarah = trim($_POST['sejarah'] ?? '');
    $struktur = trim($_POST['struktur_organisasi'] ?? '');
    
    // PERBAIKAN LOGIKA FOLDER: Menggunakan path absolut yang lebih aman
    $rootPath = dirname(__DIR__); 
    $uploadDir = $rootPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profil';
    
    // Jika path exists tapi bukan directory (file), hapus dulu
    if (file_exists($uploadDir) && !is_dir($uploadDir)) {
        @unlink($uploadDir);
    }
    
    // Buat folder jika belum ada
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0777, true)) {
            $flash = ['type'=>'danger', 'msg'=>'Gagal membuat folder upload. Periksa permission folder uploads/'];
        }
    }
    
    $uploadDir .= DIRECTORY_SEPARATOR;

    // Proses Foto Kapolres
    $foto_kapolres = $p['foto_kapolres'] ?? '';
    if (!empty($_FILES['f_kapolres']['name']) && $_FILES['f_kapolres']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['f_kapolres']['name'], PATHINFO_EXTENSION);
        $newName = 'kapolres_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['f_kapolres']['tmp_name'], $uploadDir . $newName)) {
            $foto_kapolres = $newName;
        } else {
            $flash = ['type'=>'warning', 'msg'=>'Gagal upload foto Kapolres. Folder: ' . $uploadDir];
        }
    }

    // Proses Foto Wakapolres
    $foto_wakapolres = $p['foto_wakapolres'] ?? '';
    if (!empty($_FILES['f_wakapolres']['name']) && $_FILES['f_wakapolres']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['f_wakapolres']['name'], PATHINFO_EXTENSION);
        $newName = 'wakapolres_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['f_wakapolres']['tmp_name'], $uploadDir . $newName)) {
            $foto_wakapolres = $newName;
        } else {
            $flash = ['type'=>'warning', 'msg'=>'Gagal upload foto Wakapolres. Folder: ' . $uploadDir];
        }
    }

    $stmt = $conn->prepare("UPDATE profil_settings SET 
        nama_kapolres=?, nama_wakapolres=?, visi=?, misi=?, sejarah=?, struktur_organisasi=?, 
        foto_kapolres=?, foto_wakapolres=? WHERE id=1");
    $stmt->bind_param("ssssssss", $nama_kapolres, $nama_wakapolres, $visi, $misi, $sejarah, $struktur, $foto_kapolres, $foto_wakapolres);
    
    if ($stmt->execute()) {
        $flash = ['type'=>'success', 'msg'=>'Profil Berhasil Diperbarui!'];
        // Refresh data setelah update
        $res = $conn->query("SELECT * FROM profil_settings WHERE id=1");
        $p = $res->fetch_assoc();
    } else {
        $flash = ['type'=>'danger', 'msg'=>'Gagal Update: ' . $conn->error];
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Profil | Admin Polresta Padang</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #f5b400; --dark: #1e293b; --bg: #f8fafc; }
        body { background: var(--bg); font-family: 'Poppins', sans-serif; color: #334155; }
        .wrap { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .card { background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .tab-btn { padding: 12px 20px; border: none; background: none; cursor: pointer; font-weight: 600; color: #64748b; border-bottom: 3px solid transparent; }
        .tab-btn.active { color: var(--dark); border-bottom-color: var(--primary); }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 700; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; }
        input[type="text"], textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; }
        textarea { min-height: 120px; resize: vertical; }

        /* Cari bagian .pimpinan-grid dan ganti dengan ini */
        .pimpinan-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .img-box { text-align: center; background: #f1f5f9; padding: 15px; border-radius: 12px; margin-bottom: 10px; }
        .img-box img { width: 120px; height: 160px; object-fit: cover; border-radius: 8px; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .btn-save { background: var(--dark); color: #fff; border: none; padding: 16px; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; margin-top: 20px; transition: 0.2s; }
        .btn-save:hover { background: #000; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .success { background: #dcfce7; color: #166534; }
        .danger { background: #fee2e2; color: #991b1b; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @media (max-width: 768px) {
    .pimpinan-grid { 
        grid-template-columns: 1fr; 
    }
    .wrap { 
        margin: 10px auto; 
        padding: 0 10px; 
    }
    .card { 
        padding: 15px; 
    }
    .tabs {
        overflow-x: auto; 
        white-space: nowrap;
    }
}
    </style>
</head>
<body>
    <div class="wrap">
        <div style="margin-bottom: 30px;">
            <h2 style="margin:0; font-weight: 800;"><i class="fa-solid fa-id-card-alt"></i> Kelola Profil</h2>
            <p style="color: #64748b;">Update informasi pimpinan, visi misi, dan sejarah institusi.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert <?= $flash['type'] ?>"> <?= e($flash['msg']) ?> </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'tab1')">Data Pimpinan</button>
            <button class="tab-btn" onclick="openTab(event, 'tab2')">Visi & Misi</button>
            <button class="tab-btn" onclick="openTab(event, 'tab3')">Sejarah & Struktur</button>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div id="tab1" class="tab-content active">
                <div class="card pimpinan-grid">
                    <div class="form-group">
                        <label>Kapolresta Padang</label>
                        <div class="img-box">
                            <img src="../uploads/profil/<?= e($p['foto_kapolres'] ?? 'default.jpg') ?>" alt="Kapolres">
                        </div>
                        <input type="file" name="f_kapolres" accept="image/*" style="margin-bottom:10px;">
                        <input type="text" name="nama_kapolres" value="<?= e($p['nama_kapolres'] ?? '') ?>" placeholder="Nama & Gelar">
                    </div>
                    <div class="form-group">
                        <label>Wakapolresta Padang</label>
                        <div class="img-box">
                            <img src="../uploads/profil/<?= e($p['foto_wakapolres'] ?? 'default.jpg') ?>" alt="Wakapolres">
                        </div>
                        <input type="file" name="f_wakapolres" accept="image/*" style="margin-bottom:10px;">
                        <input type="text" name="nama_wakapolres" value="<?= e($p['nama_wakapolres'] ?? '') ?>" placeholder="Nama & Gelar">
                    </div>
                </div>
            </div>

            <div id="tab2" class="tab-content">
                <div class="card">
                    <div class="form-group">
                        <label>Visi</label>
                        <textarea name="visi"><?= e($p['visi'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Misi</label>
                        <textarea name="misi"><?= e($p['misi'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div id="tab3" class="tab-content">
                <div class="card">
                    <div class="form-group">
                        <label>Sejarah</label>
                        <textarea name="sejarah"><?= e($p['sejarah'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Keterangan Struktur Organisasi</label>
                        <textarea name="struktur_organisasi"><?= e($p['struktur_organisasi'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> SIMPAN PERUBAHAN</button>
        </form>

        <p style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="text-decoration:none; color:#64748b; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
        </p>
    </div>

    <script>
        function openTab(evt, name) {
            let i, content, btns;
            content = document.getElementsByClassName("tab-content");
            for (i = 0; i < content.length; i++) content[i].classList.remove("active");
            btns = document.getElementsByClassName("tab-btn");
            for (i = 0; i < btns.length; i++) btns[i].classList.remove("active");
            document.getElementById(name).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>