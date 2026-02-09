<?php
$ALLOWED_ROLES = ['admin','editor'];
require __DIR__ . "/auth_guard.php";
require __DIR__ . "/db_connection.php";

$success_msg = "";

// LOGIKA SIMPAN (DATABASE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['config'])) {
        foreach ($_POST['config'] as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO konfigurasi_home (meta_key, meta_value) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE meta_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
    }

    $upload_dir = "../assets/";
    $foto_fields = ['kapolres_foto', 'wakapolres_foto'];
    foreach ($foto_fields as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $new_name = $field . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $new_name)) {
                $stmt = $conn->prepare("INSERT INTO konfigurasi_home (meta_key, meta_value) VALUES (?, ?) 
                                        ON DUPLICATE KEY UPDATE meta_value = ?");
                $stmt->bind_param("sss", $field, $new_name, $new_name);
                $stmt->execute();
            }
        }
    }
    $success_msg = "Konfigurasi berhasil diperbarui!";
}

// AMBIL DATA
$res = $conn->query("SELECT * FROM konfigurasi_home");
$cfg = [];
while($row = $res->fetch_assoc()) { $cfg[$row['meta_key']] = $row['meta_value']; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Home - Polresta Padang</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2d3436;
            --accent-color: #0984e3;
            --bg-gray: #f0f2f5;
        }
        body { background-color: var(--bg-gray); font-family: 'Poppins', sans-serif; color: #333; margin: 0; }
        
        .main-container { padding: 40px 20px; max-width: 1000px; margin: auto; }
        
        /* Header Style */
        .top-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-info h1 { margin: 0; font-size: 24px; font-weight: 600; color: #1e272e; }
        .page-info p { margin: 5px 0 0; color: #7f8c8d; font-size: 14px; }
        
        /* Form Card */
        .card-form { background: #ffffff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; }
        .section-box { padding: 30px; border-bottom: 1px solid #f1f1f1; }
        .section-box:last-of-type { border-bottom: none; }
        
        .section-label { 
            display: inline-block; 
            background: #f8f9fa; 
            padding: 5px 15px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
            color: var(--accent-color);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Grid Layout */
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .field-full { grid-column: span 2; }
        
        .input-group { margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: 500; color: #4b4b4b; margin-bottom: 8px; }
        input[type="text"] { 
            width: 100%; padding: 12px 15px; border: 1.5px solid #e0e0e0; border-radius: 10px; 
            font-size: 14px; transition: all 0.3s; box-sizing: border-box;
        }
        input[type="text"]:focus { border-color: var(--accent-color); outline: none; box-shadow: 0 0 0 4px rgba(9, 132, 227, 0.1); }

        /* Pimpinan Styles */
        .pimpinan-item { display: flex; gap: 20px; align-items: flex-start; padding: 15px; background: #fafafa; border-radius: 12px; }
        .img-wrap { flex-shrink: 0; }
        .img-preview { width: 80px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .upload-info { flex-grow: 1; }
        input[type="file"] { font-size: 12px; margin-top: 10px; }

        /* Buttons Style */
        .action-footer { background: #fdfdfd; padding: 25px 35px; display: flex; justify-content: flex-end; gap: 15px; border-top: 1px solid #eee; }
        
        .btn-base { 
            padding: 12px 25px; border-radius: 10px; font-size: 14px; font-weight: 500; 
            cursor: pointer; transition: all 0.3s; border: none; text-decoration: none; display: inline-flex; align-items: center;
        }
        .btn-back { background: #fff; color: #636e72; border: 1.5px solid #dfe6e9; }
        .btn-back:hover { background: #f1f2f6; border-color: #b2bec3; }
        
        .btn-cancel { background: transparent; color: #d63031; }
        .btn-cancel:hover { background: rgba(214, 48, 49, 0.05); }

        .btn-save { background: var(--primary-color); color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .btn-save:hover { background: #000; transform: translateY(-2px); }

        .alert-pop { background: #55efc4; color: #00b894; padding: 15px 25px; border-radius: 10px; margin-bottom: 25px; font-size: 14px; font-weight: 500; border-left: 5px solid #00b894; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="top-nav">
        <div class="page-info">
            <h1>Kelola Halaman Utama</h1>
            <p>Konfigurasi teks banner dan profil pimpinan di beranda.</p>
        </div>
        <a href="dashboard.php" class="btn-base btn-back">
            <span style="margin-right:8px;">←</span> Kembali ke Dashboard
        </a>
    </div>

    <?php if($success_msg): ?>
        <div class="alert-pop">✓ <?= $success_msg ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="card-form">
        
        <div class="section-box">
            <span class="section-label">Banner Hero</span>
            <div class="field-grid">
                <div class="input-group">
                    <label>Tagline Atas</label>
                    <input type="text" name="config[banner_top]" placeholder="Contoh: POLRI PRESISI" value="<?= $cfg['banner_top'] ?? '' ?>">
                </div>
                <div class="input-group">
                    <label>Judul Utama (H1)</label>
                    <input type="text" name="config[banner_title]" placeholder="Contoh: Polresta Padang" value="<?= $cfg['banner_title'] ?? '' ?>">
                </div>
                <div class="input-group field-full">
                    <label>Tagline Bawah</label>
                    <input type="text" name="config[banner_bottom]" placeholder="Masukkan deskripsi pendek..." value="<?= $cfg['banner_bottom'] ?? '' ?>">
                </div>
            </div>
        </div>

        <div class="section-box">
            <span class="section-label">Profil Pimpinan</span>
            <div class="field-grid">
                <div class="input-group">
                    <label>Nama Kapolres</label>
                    <input type="text" name="config[kapolres_nama]" value="<?= $cfg['kapolres_nama'] ?? '' ?>" style="margin-bottom:15px;">
                    <div class="pimpinan-item">
                        <div class="img-wrap">
                            <img src="../assets/<?= $cfg['kapolres_foto'] ?? 'default.jpg' ?>" class="img-preview">
                        </div>
                        <div class="upload-info">
                            <label style="margin-bottom:2px;">Ganti Foto</label>
                            <input type="file" name="kapolres_foto">
                        </div>
                    </div>
                </div>
                <div class="input-group">
                    <label>Nama Wakapolres</label>
                    <input type="text" name="config[wakapolres_nama]" value="<?= $cfg['wakapolres_nama'] ?? '' ?>" style="margin-bottom:15px;">
                    <div class="pimpinan-item">
                        <div class="img-wrap">
                            <img src="../assets/<?= $cfg['wakapolres_foto'] ?? 'default.jpg' ?>" class="img-preview">
                        </div>
                        <div class="upload-info">
                            <label style="margin-bottom:2px;">Ganti Foto</label>
                            <input type="file" name="wakapolres_foto">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-footer">
            <button type="reset" class="btn-base btn-cancel">Batal Perubahan</button>
            <button type="submit" class="btn-base btn-save">Simpan Perubahan</button>
        </div>
    </form>
</div>

</body>
</html>